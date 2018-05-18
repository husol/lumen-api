<?php

namespace App\Console\Commands;

use App\DataServices\Device\DeviceRepo;
use App\DataServices\Job\JobRepo;
use App\DataServices\NotiMessage\NotiMessageRepo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindRatingCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "PeopleFeedback:rating";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Remind recruiters and candidates to do rating if any.";

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
        $this->info('Start. Remind recruiters and candidates to do rating after finishing the job 1 DAY.');

        //Get all jobs which have status = 1 and max_end_datetime is CURDATE - 1 DAY
        $sql = "SELECT jobs.id, jobs.id_user, jobs.id_company, MAX(job_times.end_datetime) AS max_end_datetime
                FROM job_times JOIN jobs ON jobs.id = job_times.`id_job`
                WHERE jobs.`status` = 1
                GROUP BY jobs.id_user, job_times.id_job
                HAVING DATE_FORMAT(max_end_datetime, '%Y-%m-%d') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";

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
                        ->where('people_jobs.status', 2)
                        ->where('people_jobs.checked_in_at', '<>', '0000-00-00 00:00:00')
                        ->whereNotNull('people_jobs.checked_in_at')
                        ->first([DB::raw("GROUP_CONCAT(lit_people.u_id) AS user_ids")]);

            if (empty($result->user_ids)) {
                continue;
            }
            $myJob = $repoJob->getJob($job->id);
            //Remind candidates
            $idUsers = explode(',', $result->user_ids);

            //Build notification data
            $data = [
                'feature' => 'job_done',
                'entity' => 'job',
                'id' => $myJob->id,
                'action' => 'open',
                "extra_info" => ['is_job_social' => $myJob->is_job_social]
            ];

            //Build notification message
            $repoNotiMsg = new NotiMessageRepo();
            $notiMsg = $repoNotiMsg->find('noti_msg_remind_candidate_rating');
            $message = sprintf($notiMsg->model_msg, $myJob->name);

            $repoDevice->pushNotification($idUsers, $data, $message);
            //Remind recruiters
            //Build notification data
            $data = [
                'feature' => 'job_history',
                'entity' => 'job',
                'id' => $myJob->id,
                'action' => 'open',
                "extra_info" => ['is_job_social' => $myJob->is_job_social]
            ];

            //Build notification message
            $repoNotiMsg = new NotiMessageRepo();
            $notiMsg = $repoNotiMsg->find('noti_msg_remind_recruiter_rating');
            $message = sprintf($notiMsg->model_msg, $myJob->name);

            $repoDevice->pushNotification([$myJob->id_user], $data, $message);
        }

        $this->info('End. Reminded recruiters and candidates to do rating after finishing the job 1 DAY.');
        return true;
    }
}
