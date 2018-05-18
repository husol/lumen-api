<?php

namespace App\Console\Commands;

use App\DataServices\Device\DeviceRepo;
use App\DataServices\Job\JobRepo;
use App\DataServices\NotiMessage\NotiMessageRepo;
use App\Models\PeopleJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindCheckinCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "PeopleJob:checkin";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Remind recruiters and candidates to check in if any.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Start. Remind recruiters and candidates to check in before the starting work 1 hour.');

        //Get all jobs which have status = 1 and min_start_datetime is NOW + 1 hour
        $sql = "SELECT jobs.id, jobs.id_user, jobs.id_company, MIN(job_times.`start_datetime`) AS min_start_datetime
                FROM job_times JOIN jobs ON jobs.id = job_times.`id_job`
                WHERE jobs.`status` = 1
                GROUP BY jobs.id_user, job_times.id_job
                HAVING DATE_FORMAT(min_start_datetime, '%Y-%m-%d %H:%i')".
                " = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 HOUR), '%Y-%m-%d %H:%i')";

        $jobs = DB::select($sql);

        if (empty($jobs)) {
            $this->info('End. No job for reminder.');
            return false;
        }

        $repoDevice = new DeviceRepo();
        $repoJob = new JobRepo();
        foreach ($jobs as $job) {
            $result = DB::table('people_jobs')
                        ->join('lit_people', 'people_jobs.id_people', '=', 'lit_people.p_id')
                        ->where('people_jobs.id_job', $job->id)
                        ->where('people_jobs.status', PeopleJob::STATUS_SELECTED)
                        ->first([DB::raw("GROUP_CONCAT(lit_people.u_id) AS user_ids")]);

            if (empty($result->user_ids)) {
                continue;
            }
            //Remind candidates
            $idUsers = explode(',', $result->user_ids);
            $myJob = $repoJob->getJob($job->id);
            //Build notification data
            $data = [
                'feature' => 'job',
                'entity' => 'people_job',
                'id' => intval($myJob->id),
                'action' => 'checkin',
                "extra_info" => ['is_job_social' => intval($myJob->is_job_social)]
            ];

            //Build notification message
            $repoNotiMsg = new NotiMessageRepo();
            $notiMsg = $repoNotiMsg->find('noti_msg_remind_candidate_checkin');
            $message = sprintf($notiMsg->model_msg, $myJob->name);

            $repoDevice->pushNotification($idUsers, $data, $message);
            //Remind recruiters
            //Build notification data
            $data = [
                'feature' => 'job',
                'entity' => 'people_job',
                'id' => intval($myJob->id),
                'action' => 'checkin',
                "extra_info" => [
                    'id_company' => intval($myJob->id_company),
                    'is_job_social' => intval($myJob->is_job_social)
                ]
            ];

            //Build notification message
            $repoNotiMsg = new NotiMessageRepo();
            $notiMsg = $repoNotiMsg->find('noti_msg_remind_recruiter_checkin');
            $message = sprintf($notiMsg->model_msg, $myJob->name);

            $repoDevice->pushNotification([$job->id_user], $data, $message);
        }

        $this->info('End. Reminded recruiters and candidates to check in before the starting work 30 mins.');
        return true;
    }
}
