<?php

namespace App\DataServices\Job;

use App\DataServices\EloquentRepo;
use App\DataServices\PackageType\PackageTypeRepo;
use App\DataServices\PeopleJob\PeopleJobRepo;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

class JobRepo extends EloquentRepo implements JobRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Job::class;
    }

    public function getJobList($arrFilter = [])
    {
        $selectedFields = [
            'jobs.id',
            'jobs.id_company',
            'jobs.id_job_group',
            'lit_company.c_name AS company_name',
            'lit_company.c_logo AS company_logo',
            'jobs.description',
            'jobs.quantity',
            'jobs.working_address',
            'jobs.salary',
            'jobs.deadline',
            'jobs.is_featured',
            'jobs.except_worked_period',
            'is_job_social',
            'package_types.people_cate_ids',
            'package_types.name'
        ];

        $jobs = $this->model->join('job_packages', 'job_packages.id_job', '=', 'jobs.id')
            ->join('packages', 'job_packages.id_package', '=', 'packages.id')
            ->join('package_types', 'packages.id_package_type', '=', 'package_types.id')
            ->join('package_categories', 'package_categories.id', '=', 'package_types.id_package_category')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->groupBy('jobs.id');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $comparison = '=';
                $val = $value;
                if (is_array($value)) {
                    $comparison = strval($value[0]);
                    $val = $value[1];
                }
                $jobs->where($field, $comparison, $val);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $jobs->orderBy($field, $value);
            }
        }
        $jobs->orderBy('jobs.id', 'DESC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $jobs->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $jobs->get($selectedFields);
        }

        return $jobs->paginate(Job::PER_PAGE, $selectedFields);
    }

    public function getJob($id)
    {
        $selectedFields = [
            'jobs.id',
            'jobs.id_user',
            'jobs.id_company',
            'jobs.id_job_group',
            'lit_company.c_name AS company_name',
            'lit_company.c_logo AS company_logo',
            'lit_company.c_phone AS company_phone',
            'jobs.description',
            'jobs.quantity',
            'jobs.working_address',
            'jobs.total',
            'jobs.salary',
            'jobs.deadline',
            'jobs.is_featured',
            'jobs.except_worked_period',
            'is_job_social',
            'jobs.status',
            'package_types.name',
            'packages.kind',
            'target_follow',
            'target_like'
        ];

        $job = $this->model->join('job_packages', 'job_packages.id_job', '=', 'jobs.id')
            ->join('packages', 'job_packages.id_package', '=', 'packages.id')
            ->join('package_types', 'packages.id_package_type', '=', 'package_types.id')
            ->join('package_categories', 'package_categories.id', '=', 'package_types.id_package_category')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->where('jobs.id', $id)
            ->groupBy('jobs.id');

        return $job->first($selectedFields);
    }

    public function getAppliedPeopleJobs($loggedUser)
    {
        $selectedFields = [
            'jobs.id',
            'lit_company.c_name AS company_name',
            'lit_company.c_logo AS company_logo',
            'lit_company.c_phone AS company_phone',
            'jobs.description',
            'jobs.quantity',
            'jobs.working_address',
            'jobs.salary',
            'jobs.deadline',
            'jobs.is_featured',
            'is_job_social',
            'package_types.name'
        ];

        $jobs = $this->model->join('job_packages', 'job_packages.id_job', '=', 'jobs.id')
            ->join('packages', 'job_packages.id_package', '=', 'packages.id')
            ->join('package_types', 'packages.id_package_type', '=', 'package_types.id')
            ->join('package_categories', 'package_categories.id', '=', 'package_types.id_package_category')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->join('people_jobs', 'jobs.id', '=', 'people_jobs.id_job')
            ->where('id_people', $loggedUser->id_people)
            ->where('people_jobs.status', 1)
            ->groupBy('jobs.id')->orderBy('deadline', 'DESC');

        return $jobs->paginate(Job::PER_PAGE, $selectedFields);
    }

    public function getOnGoingPeopleJobs($loggedUser)
    {
        $selectedFields = [
            'jobs.id',
            'jobs.id_company',
            'lit_company.c_name AS company_name',
            'lit_company.c_logo AS company_logo',
            'lit_company.c_phone AS company_phone',
            'jobs.description',
            'jobs.quantity',
            'jobs.working_address',
            'jobs.salary',
            'jobs.deadline',
            'jobs.is_featured',
            'is_job_social',
            'checked_in_at',
            'target_follow',
            'target_like',
            'package_types.name'
        ];

        $jobs = $this->model->join('job_packages', 'job_packages.id_job', '=', 'jobs.id')
            ->join('packages', 'job_packages.id_package', '=', 'packages.id')
            ->join('package_types', 'packages.id_package_type', '=', 'package_types.id')
            ->join('package_categories', 'package_categories.id', '=', 'package_types.id_package_category')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->join('people_jobs', 'jobs.id', '=', 'people_jobs.id_job')
            ->where('id_people', $loggedUser->id_people)
            ->where('people_jobs.status', 2)
            ->where(function ($query) {
                $query->whereNull('people_jobs.checked_in_at')
                ->orWhere('people_jobs.checked_in_at', '0000-00-00 00:00:00');
            })->groupBy('jobs.id')->orderBy('deadline', 'DESC');

        return $jobs->paginate(Job::PER_PAGE, $selectedFields);
    }

    public function getDonePeopleJobs($id_people)
    {
        $selectedFields = [
            'jobs.id',
            'jobs.id_company',
            'lit_company.c_name AS company_name',
            'lit_company.c_logo AS company_logo',
            'jobs.description',
            'jobs.quantity',
            'jobs.working_address',
            'jobs.salary',
            'jobs.deadline',
            'jobs.is_featured',
            'jobs.status',
            'is_job_social',
            'package_types.name'
        ];

        $jobs = $this->model->join('job_packages', 'job_packages.id_job', '=', 'jobs.id')
            ->join('packages', 'job_packages.id_package', '=', 'packages.id')
            ->join('package_types', 'packages.id_package_type', '=', 'package_types.id')
            ->join('package_categories', 'package_categories.id', '=', 'package_types.id_package_category')
            ->join('lit_company', 'lit_company.c_id', '=', 'jobs.id_company')
            ->join('people_jobs', 'jobs.id', '=', 'people_jobs.id_job')
            ->where('id_people', $id_people)
            ->where('people_jobs.status', 2)
            ->where('people_jobs.checked_in_at', '<>', '0000-00-00 00:00:00')
            ->whereNotNull('people_jobs.checked_in_at')
            ->groupBy('jobs.id')->orderBy('deadline', 'DESC');

        return $jobs->paginate(Job::PER_PAGE, $selectedFields);
    }

    public function getJobTimeByShift($job_times)
    {
        //Split time with 17:00
        $jobTimeSplited = [];
        foreach ($job_times as $jobTime) {
            if (is_array($jobTime)) {
                $startDateTime = $jobTime['start_datetime'];
                $endDateTime = $jobTime['end_datetime'];
            } else {
                $startDateTime = $jobTime->start_datetime;
                $endDateTime = $jobTime->end_datetime;
            }
            $date = date('Y-m-d', strtotime($startDateTime));
            $jobTimeSplited[$date]['day'] = 0;
            $jobTimeSplited[$date]['night'] = 0;
            if ($startDateTime < "$date 17:00" && $endDateTime > "$date 17:00") {
                $jobTimeSplited[$date]['slots'][] = [
                    'start_datetime' => $startDateTime,
                    'end_datetime' => "$date 16:59"
                ];
                $jobTimeSplited[$date]['slots'][] = [
                    'start_datetime' => "$date 17:00",
                    'end_datetime' => $endDateTime
                ];
                continue;
            }

            $jobTimeSplited[$date]['slots'][] = ['start_datetime' => $startDateTime, 'end_datetime' => $endDateTime];
        }

        //Compute total hours foreach date
        foreach ($jobTimeSplited as $date => $jobTime) {
            foreach ($jobTime['slots'] as $slot) {
                if ($slot['start_datetime'] >= "$date 17:00") {
                    $jobTimeSplited[$date]['night'] = 1;
                    continue;
                }
                $hours = (strtotime($slot['end_datetime']) - strtotime($slot['start_datetime']))/3600;
                $jobTimeSplited[$date]['day'] += $hours;
            }
        }

        //Ok, decide the final job times
        $finalJobTimes = [];
        foreach ($jobTimeSplited as $date => $jobTime) {
            if ($jobTime['night'] > 0) {
                $finalJobTimes[$date][] = 'night_shift';
            }
            if ($jobTime['day'] > 4) {
                $finalJobTimes[$date][] = 'two_shift';
            } elseif ($jobTime['day'] > 0) {
                $finalJobTimes[$date][] = 'one_shift';
            }
        }
        return $finalJobTimes;
    }

    public function getCalendarMonthOfPeople($id_people, $year_month)
    {
        $sql = "SELECT people_jobs.id_job, lit_company.c_name AS company_name, jobs.working_address,
                  DATE_FORMAT(job_times.start_datetime, '%Y-%m-%d') AS `date`,
                  DATE_FORMAT(job_times.start_datetime, '%H:%i') AS start_time,
                  DATE_FORMAT(job_times.end_datetime, '%H:%i') AS end_time
                FROM job_times JOIN people_jobs ON people_jobs.id_job = job_times.id_job
	              JOIN jobs ON jobs.id = people_jobs.id_job
	              JOIN lit_company ON jobs.id_company = lit_company.c_id
                WHERE people_jobs.id_people = $id_people
                  AND DATE_FORMAT(job_times.start_datetime, '%Y-%m') = '$year_month'
                ORDER BY `date` ASC, `start_time` ASC";

        $results = DB::select($sql);

        $repoPackageType = new PackageTypeRepo();
        $calendar = [];
        foreach ($results as $result) {
            $packageType = $repoPackageType->getPackageTypeByJobId($result->id_job);
            $result->package_type_name = $packageType->name;
            $result->is_job_social = $packageType->is_job_social;
            $calendar[$result->date][] = $result;
        }

        return $calendar;
    }

    public function deleteJob($id)
    {
        //Delete people_job_posts
        DB::table('people_job_posts')->where('id_job', $id)->delete();

        //Delete people_jobs
        $repoPeopleJob = new PeopleJobRepo();
        $repoPeopleJob->deleteWhere(['id_job' => $id]);

        //Delete job_packages
        DB::table('job_packages')->where('id_job', $id)->delete();

        //Delete job_times
        DB::table('job_times')->where('id_job', $id)->delete();

        //Delete job
        $this->delete($id);
    }
}
