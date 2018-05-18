<?php

namespace App\DataServices\PeopleJob;

use App\Common;
use App\DataServices\EloquentRepo;
use App\Models\Job;
use App\Models\PeopleJob;
use Illuminate\Support\Facades\DB;

class PeopleJobRepo extends EloquentRepo implements PeopleJobRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PeopleJob::class;
    }

    public function getPeopleJob($id_people, $id_job)
    {
        $peopleJob = $this->findWhere([
            'id_people' => $id_people,
            'id_job' => $id_job
        ], ['checked_in_at', 'status', 'created_at'])->first();

        return $peopleJob;
    }

    public function getByPeopleId($id_people, $where = [])
    {
        $selectedFields = [
            'lit_company.c_logo AS logo',
            'people_jobs.id_job',
            'people_jobs.checked_in_at',
            'people_jobs.status'
        ];
        $peopleJobs = $this->model->join('jobs', 'jobs.id', '=', 'people_jobs.id_job')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->where('id_people', $id_people);
        if (!empty($where)) {
            $peopleJobs->where($where);
        }
        $peopleJobs->orderBy('people_jobs.updated_at', 'DESC');

        return $peopleJobs->get($selectedFields);
    }

    public function getByJobId($id_job, $where = [])
    {
        $selectedFields = [
            'lit_ac_user.u_avatar AS avatar',
            'people_jobs.id_people',
            'people_jobs.checked_in_at',
            'people_jobs.status'
        ];
        $peopleJobs = $this->model->join('lit_people', 'lit_people.p_id', '=', 'people_jobs.id_people')
            ->join('lit_ac_user', 'lit_ac_user.u_id', '=', 'lit_people.u_id')
            ->where('id_job', $id_job);
        if (!empty($where)) {
            $peopleJobs->where($where);
        }
        $peopleJobs->orderBy('people_jobs.updated_at', 'DESC');

        return $peopleJobs->get($selectedFields);
    }

    //Check if candidate busy in job_times of the job
    public function checkIfBusyInTimes($id_people, $id_job)
    {
        //Get job_times of the job
        $jobTimes = DB::table('job_times')->where('id_job', $id_job)
            ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);

        if ($jobTimes->isEmpty()) {
            return false;
        }
        $jobTimes = $jobTimes->toArray();

        //Get all job_times of candidate which between minStartDatetime and maxEndDatetime
        $busyTimes = $this->model->join('job_times', 'people_jobs.id_job', '=', 'job_times.id_job')
                ->where('people_jobs.status', PeopleJob::STATUS_SELECTED)
                ->where('people_jobs.id_people', $id_people)
                ->get(['start_datetime', 'end_datetime']);

        if ($busyTimes->isEmpty()) {
            return false;
        }
        $busyTimes = $busyTimes->toArray();

        /*Two time periods P1 and P2 overlaps if, and only if, at least one of these conditions hold:
        P1 starts between the start and end of P2 (P2.from <= P1.from <= P2.to)
        P2 starts between the start and end of P1 (P1.from <= P2.from <= P1.to)*/
        //Process to compare one by one
        foreach ($jobTimes as $jobTime) {
            foreach ($busyTimes as $busyTime) {
                if (($busyTime['start_datetime'] >= $jobTime->start_datetime &&
                        $busyTime['start_datetime'] <= $jobTime->end_datetime) ||
                    ($jobTime->start_datetime >= $busyTime['start_datetime'] &&
                        $jobTime->start_datetime <= $busyTime['end_datetime'])
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    //Check if candidate used to work in job group of job before
    public function checkIfWorkedGroupBefore($id_people, $jobObj)
    {
        if (is_array($jobObj)) {
            $jobObj = (object) $jobObj;
        }

        $groupPeopleIds = $this->model->join('jobs', function ($join) use ($jobObj) {
            $join->on('people_jobs.id_job', '=', 'jobs.id');
            $join->on('jobs.id', '<>', DB::raw($jobObj->id));
        })->where('people_jobs.status', PeopleJob::STATUS_SELECTED)
            ->where('people_jobs.id_people', $id_people)
            ->where('jobs.status', Job::STATUS_ENABLE)
            ->where('jobs.id_job_group', $jobObj->id_job_group)
            ->whereRaw("jobs.deadline > DATE_SUB(NOW(), INTERVAL $jobObj->except_worked_period MONTH)")
            ->distinct()->get(["people_jobs.id_people"]);

        return !$groupPeopleIds->isEmpty();
    }
}
