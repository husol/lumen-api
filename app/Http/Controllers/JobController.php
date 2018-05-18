<?php

namespace App\Http\Controllers;

use App\DataServices\CompanyFeedback\CompanyFeedbackRepo;
use App\DataServices\Device\DeviceRepo;
use App\DataServices\Job\JobRepoInterface;
use App\DataServices\NotiMessage\NotiMessageRepo;
use App\DataServices\Package\PackageRepo;
use App\DataServices\People\PeopleRepo;
use App\DataServices\PeopleFeedback\PeopleFeedbackRepo;
use App\DataServices\PeopleJob\PeopleJobRepo;
use App\Firebase;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Error;
use App\Common;

class JobController extends Controller
{
    protected $repoJob;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(JobRepoInterface $repoJob)
    {
        $this->repoJob = $repoJob;
    }

    public function getList(Request $request)
    {
        //In case of getting by id_people and type
        if ($request->has('id_people')) {
            $this->validate($request, [
                'id_people' => 'required|numeric',
                'type' => 'required|in:done'
            ]);

            $idPeople = $request->input('id_people');
            $jobs = $this->getDoneJobs($idPeople);

            return responseJson($jobs);
        }

        //Other cases
        $arrFilter = [];
        //Filter if any
        $isFeatured = $request->input('is_featured');
        if (!empty($isFeatured)) {
            $arrFilter['where']['is_featured'] = $isFeatured;
        }
        $idCompany = $request->input('id_company');
        if (!empty($idCompany)) {
            $arrFilter['where']['id_company'] = $idCompany;
        }
        $status = $request->input('status');
        if (!empty($status)) {
            $arrFilter['where']['jobs.status'] = $status;
        }

        $sortBy = $request->input('sort_by');
        $sortType = $request->input('sort_type');
        $limit = $request->input('limit');
        if (!empty($sortBy)) {
            $arrFilter['order'][$sortBy] = empty($sortType) ? 'DESC' : $sortType;
        }
        if (!empty($limit)) {
            $arrFilter['limit'] = $limit;
        }

        $jobs = $this->repoJob->getJobList($arrFilter);

        $err = new Error();
        if (empty($jobs)) {
            return responseJson([]);
        }

        $listJob = $jobs->toArray();

        $dataJob = $listJob;
        if (isset($listJob['data'])) {
            $dataJob = $listJob['data'];
        }

        foreach ($dataJob as $k => $job) {
            //Format if necessary
            $dataJob[$k]['salary'] = floatval($job['salary']);
            $dataJob[$k]['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get company_feedbacks of job if necessary
            if ($idCompany > 0) {
                $repoCompanyFeedback = new CompanyFeedbackRepo();
                $obj = new \stdClass();
                $obj->type = 'job';
                $obj->id = $job['id'];
                $companyFeedbacks = $repoCompanyFeedback->getByCompanyId($idCompany, $obj);

                $feedBacks = [];
                $dataJob[$k]['feedbacks'] = [];
                if (!$companyFeedbacks->isEmpty()) {
                    $companyFeedbacks = $companyFeedbacks->toArray();
                    $sortCriteria = ['rating' => [SORT_DESC, SORT_NUMERIC]];
                    $companyFeedbacks = sortArrayByKey($companyFeedbacks, $sortCriteria);
                    $count = 0;
                    foreach ($companyFeedbacks as $companyFeedback) {
                        if ($count == 2) {
                            break;
                        }
                        if (!empty($companyFeedback['user_avatar'])) {
                            $companyFeedback['user_avatar'] = Common::getImgUrl().$companyFeedback['user_avatar'];
                        }
                        if (!empty($companyFeedback['company_logo'])) {
                            $companyFeedback['company_logo'] = Common::getImgUrl().$companyFeedback['company_logo'];
                        }
                        $feedBacks[] = $companyFeedback;
                        $count++;
                    }
                }
                $dataJob[$k]['feedbacks'] = $feedBacks;
            }
        }

        if (isset($listJob['data'])) {
            $listJob['data'] = $dataJob;
            return responseJson($listJob);
        }

        return responseJson($dataJob);
    }

    public function getDetail(Request $request, $id)
    {
        $err = new Error();
        $jobObj = $this->repoJob->getJob($id);
        if (is_null($jobObj)) {
            $err->setError('not_found', "Not found job with id = $id");
            return responseJson($err->getErrors(), 404);
        }

        $job = $jobObj->toArray();

        //Get people job status
        $loggedUser = Common::getLoggedUserInfo();
        $repoPeopleJob = new PeopleJobRepo();
        if (isset($loggedUser->id_people) && $loggedUser->id_people > 0) {
            $peopleJob = $repoPeopleJob->getPeopleJob($loggedUser->id_people, $id);
            if (!empty($peopleJob)) {
                $job['people_job_status'] = 0;
                if ($peopleJob->status == 1) {
                    $job['people_job_status'] = 1;
                    $job['people_job_applied_at'] = $peopleJob->created_at->toDateTimeString();
                } if ($peopleJob->status == 2) {
                    $job['people_job_status'] = 2;
                    $job['people_job_checked_in_at'] = $peopleJob->checked_in_at;
                    $job['rating'] = 0;
                    //Get company_feedback rating
                    $repoCompanyFeedback = new CompanyFeedbackRepo();
                    $obj = new \stdClass();
                    $obj->type = 'job';
                    $obj->id = $job['id'];
                    $companyFeedbacks = $repoCompanyFeedback->getByUserCompanyId(
                        $loggedUser->id,
                        $job['id_company'],
                        $obj
                    );
                    if (!$companyFeedbacks->isEmpty()) {
                        $companyFeedbacks = $companyFeedbacks->toArray();
                        $job['rating'] = $companyFeedbacks[0]['rating'];
                    }
                }
            }

            //Get fb_post_id
            $peopleJobPost = DB::table('people_job_posts')
                    ->where('id_people', $loggedUser->id_people)
                    ->where('id_job', $job['id'])
                    ->first(['fb_id_post', 'countlike', 'countshare', 'countcomment']);
            if (!is_null($peopleJobPost)) {
                $job['fb_id_post'] = $peopleJobPost->fb_id_post;
                $job['countlike'] = $peopleJobPost->countlike;
                $job['countshare'] = $peopleJobPost->countshare;
                $job['countcomment'] = $peopleJobPost->countcomment;
            }
        }

        //Adjust job info
        if (!empty($job['company_logo'])) {
            $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
        }
        $job['total'] = floatval($job['total']);
        $job['salary'] = floatval($job['salary']);
        $job['deadline'] = convertToDateDisplay(strtotime($job['deadline']), true);
        $job['post_quantity'] = 1;
        $job['target_follow'] = intval($job['target_follow']);
        $job['target_like'] = intval($job['target_like']);

        //Get job_times
        $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
            ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
        $job['job_times'] = [];

        if (!$jobTimes->isEmpty()) {
            $jobTimes = $jobTimes->toArray();
            $minStartDateTime = $jobTimes[0]->start_datetime;
            foreach ($jobTimes as $jobTime) {
                if ($minStartDateTime > $jobTime->start_datetime) {
                    $minStartDateTime = $jobTime->start_datetime;
                }
                $slot = [
                    'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                    'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                ];
                $job['job_times'][] = $slot;
            }
            $job['expired_datetime'] = $minStartDateTime;
        }

        if (isset($loggedUser->id_company) || isset($loggedUser->id_people)) {
            //Get people who applied the job, also return status if selected or not
            $conditions = [
                ['status', '>', 0]
            ];
            $peopleJobs = $repoPeopleJob->getByJobId($job['id'], $conditions);

            if ($peopleJobs->isEmpty()) {
                $peopleJobs = [];
            } else {
                $peopleJobs = $peopleJobs->toArray();
                //Adjust people job info
                foreach ($peopleJobs as $k => $peopleJob) {
                    if (!empty($peopleJob['avatar'])) {
                        $peopleJobs[$k]['avatar'] = Common::getImgUrl() . $peopleJob['avatar'];
                    }
                }
            }

            $job['applied_candidates'] = $peopleJobs;
        }

        //Increase countview
        $this->repoJob->update($job['id'], ['countview' => DB::raw('countview + 1')]);

        return responseJson($job);
    }

    public function getRecruitingJobs(Request $request)
    {
        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "Not found id_company with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $arrFilter['where']['id_company'] = $loggedUser->id_company;
        $arrFilter['where']['deadline'] = ['>', date('Y-m-d H:i:s')];
        $arrFilter['no_paging'] = 1;
        $jobs = $this->repoJob->getJobList($arrFilter);

        if ($jobs->isEmpty()) {
            return responseJson([]);
        }

        $jobs = $jobs->toArray();

        $repoPeopleJob = new PeopleJobRepo();

        $listJob = [];
        foreach ($jobs as $job) {
            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            //Get people who applied the job, also return status if selected or not
            $conditions =[
                ['status', '>', 0]
            ];
            $peopleJobs = $repoPeopleJob->getByJobId($job['id'], $conditions);

            if ($peopleJobs->isEmpty()) {
                $peopleJobs = [];
            } else {
                $peopleJobs = $peopleJobs->toArray();
                //Adjust people job info
                foreach ($peopleJobs as $k => $peopleJob) {
                    if (!empty($peopleJob['avatar'])) {
                        $peopleJobs[$k]['avatar'] = Common::getImgUrl().$peopleJob['avatar'];
                    }
                }
            }

            $job['applied_candidates'] = $peopleJobs;

            $listJob[] = $job;
        }

        return responseJson($listJob);
    }

    public function getHistoryJobs(Request $request)
    {
        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "Not found id_company with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $arrFilter['where']['id_company'] = $loggedUser->id_company;
        $arrFilter['where']['deadline'] = ['<=', date('Y-m-d H:i:s')];
        $arrFilter['where']['jobs.status'] = 1;
        $arrFilter['no_paging'] = 1;
        $jobs = $this->repoJob->getJobList($arrFilter);

        if ($jobs->isEmpty()) {
            return responseJson([]);
        }

        $jobs = $jobs->toArray();

        $repoPeopleJob = new PeopleJobRepo();
        $repoPeopleFeedback = new PeopleFeedbackRepo();

        $listJob = [];
        foreach ($jobs as $job) {
            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            //Get people who applied the job
            $conditions =[
                ['status', '>', 0]
            ];
            $peopleJobs = $repoPeopleJob->getByJobId($job['id'], $conditions);

            //Get rating foreach people job
            $obj = new \stdClass();
            $obj->type = 'job';
            $obj->id = $job['id'];
            foreach ($peopleJobs as $k => $peopleJob) {
                //Adjust people job info
                //For avatar
                if (!empty($peopleJob['avatar'])) {
                    $peopleJobs[$k]['avatar'] = Common::getImgUrl().$peopleJob['avatar'];
                }
                //For rating
                $peopleJobs[$k]['rating'] = 0;
                $peopleFeedbacks = $repoPeopleFeedback->getByPeopleId($peopleJob['id_people'], $obj);
                if (!$peopleFeedbacks->isEmpty()) {
                    $peopleJobs[$k]['rating'] = $peopleFeedbacks[0]['pf_rating'];
                }
            }
            $job['applied_candidates'] = $peopleJobs;

            $listJob[] = $job;
        }

        return responseJson($listJob);
    }

    public function getCalendar(Request $request)
    {
        $this->validate($request, [
            'year_month' => 'required|date_format:Y-m'
        ]);

        $loggedUser = Common::getLoggedUserInfo();

        $err = new Error();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found people with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $idPeople = $loggedUser->id_people;
        $yearMonth = $request->input('year_month');
        $calendar = $this->repoJob->getCalendarMonthOfPeople($idPeople, $yearMonth);

        return responseJson($calendar);
    }

    public function getMatchingJobs(Request $request)
    {
        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found id_people with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $arrFilter['where']['deadline'] = ['>', date('Y-m-d H:i:s')];
        $arrFilter['where']['jobs.status'] = 1;
        $arrFilter['no_paging'] = 1;
        $jobs = $this->repoJob->getJobList($arrFilter);

        if ($jobs->isEmpty()) {
            return responseJson([]);
        }

        //Get people categories to check
        $repoPeople = new PeopleRepo();
        $myPeople = $repoPeople->find($loggedUser->id_people);

        $peopleCates = [];
        for ($i = 1; $i <= 5; $i++) {
            $cate = "p_cat$i";
            if ($myPeople->$cate > 0) {
                $peopleCates[] = $myPeople->$cate;
            }
        }

        $peopleJobRepo = new PeopleJobRepo();
        $jobs = $jobs->toArray();

        $listJob = [];
        foreach ($jobs as $job) {
            $majors = explode(',', $job['people_cate_ids']);
            $arrCheck = array_intersect($peopleCates, $majors);
            if (empty($arrCheck)) {
                continue;
            }
            //Check if job has job_group and people not satisfied except_worked_period
            if ($job['id_job_group'] > 0 && $peopleJobRepo->checkIfWorkedGroupBefore($myPeople->id, $job)) {
                continue;
            }

            //Check if people already applied the job
            $peopleJob = DB::table('people_jobs')->where('id_people', $loggedUser->id_people)
                ->where('id_job', $job['id'])->first();

            if (!empty($peopleJob)) {
                continue;
            }

            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            $listJob[] = $job;
        }

        return responseJson($listJob);
    }

    public function getAppliedJobs()
    {
        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found id_people with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $appliedJobs = $this->repoJob->getAppliedPeopleJobs($loggedUser);

        if ($appliedJobs->isEmpty()) {
            return responseJson([]);
        }

        $listJob = $appliedJobs->toArray();
        $dataJob = $listJob['data'];
        $appliedJobs = [];
        foreach ($dataJob as $k => $job) {
            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            $appliedJobs[] = $job;
        }
        $listJob['data'] = $appliedJobs;

        return responseJson($listJob);
    }

    public function getOnGoingJobs()
    {
        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found id_people with logged-in user id = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $onGoingJobs = $this->repoJob->getOnGoingPeopleJobs($loggedUser);

        if ($onGoingJobs->isEmpty()) {
            return responseJson([]);
        }

        $listJob = $onGoingJobs->toArray();
        $dataJob = $listJob['data'];
        $repoPeopleJob = new PeopleJobRepo();
        $repoCompanyFeedback = new CompanyFeedbackRepo();
        $onGoingJobs = [];
        foreach ($dataJob as $k => $job) {
            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            //Get people who are selected on the job
            $conditions =[
                ['people_jobs.status', '=', 2]
            ];
            $peopleJobs = $repoPeopleJob->getByJobId($job['id'], $conditions);

            if ($peopleJobs->isEmpty()) {
                $peopleJobs = [];
            } else {
                $peopleJobs = $peopleJobs->toArray();
                //Adjust people job info
                foreach ($peopleJobs as $k => $peopleJob) {
                    if (!empty($peopleJob['avatar'])) {
                        $peopleJobs[$k]['avatar'] = Common::getImgUrl().$peopleJob['avatar'];
                    }
                }
            }

            $job['selected_candidates'] = $peopleJobs;

            //Get people_job_post if any
            if ($job['is_job_social']) {
                $peopleJobPosts = DB::table('people_job_posts')
                    ->where('id_people', $loggedUser->id_people)
                    ->where('id_job', $job['id'])
                    ->get(['fb_id_post', 'countlike', 'countcomment', 'countshare']);
                if (!$peopleJobPosts->isEmpty()) {
                    $fbPostId = explode('_', $peopleJobPosts[0]->fb_id_post);
                    $fbPostId = end($fbPostId);
                    $peopleJobPosts[0]->fb_id_post = $fbPostId;
                    $job['job_post'] = $peopleJobPosts[0];
                }
            }

            //Get company_feedback if any
            $companyFeedbacks = $repoCompanyFeedback->findWhere([
                'id_user' => $loggedUser->id,
                'id_company' => $job['id_company'],
                'object_type' => 'job',
                'object_id' => $job['id']
            ]);
            if (!$companyFeedbacks->isEmpty()) {
                $job['rating'] = $companyFeedbacks[0]['rating'];
            }

            $onGoingJobs[] = $job;
        }
        $listJob['data'] = $onGoingJobs;

        return responseJson($listJob);
    }

    public function getDoneJobs($id_people)
    {
        $doneJobs = $this->repoJob->getDonePeopleJobs($id_people);

        if ($doneJobs->isEmpty()) {
            return [];
        }

        $repoPeople = new PeopleRepo();
        $myPeople = $repoPeople->find($id_people);

        $repoCompanyFeedback = new CompanyFeedbackRepo();

        $listJob = $doneJobs->toArray();
        $dataJob = $listJob['data'];
        $doneJobs = [];
        foreach ($dataJob as $k => $job) {
            //Format if necessary
            if (!empty($job['company_logo'])) {
                $job['company_logo'] = Common::getImgUrl() . convertImageUrlByType($job['company_logo'], 'medium');
            }
            $job['salary'] = floatval($job['salary']);
            $job['deadline_formatted'] = convertToDateDisplay(strtotime($job['deadline']), true);

            //Get company feedback on the job corresponding to the people
            $obj = new \stdClass();
            $obj->type = 'job';
            $obj->id = $job['id'];

            $companyFeedbacks = $repoCompanyFeedback->getByUserCompanyId($myPeople->u_id, $job['id_company'], $obj);

            if (!$companyFeedbacks->isEmpty()) {
                $companyFeedbacks = $companyFeedbacks->toArray();
                $companyFeedback = $companyFeedbacks[0];
                if (!empty($companyFeedback['user_avatar'])) {
                    $companyFeedback['user_avatar'] = Common::getImgUrl() .
                        convertImageUrlByType($companyFeedback['user_avatar'], 'medium');
                }
                if (!empty($companyFeedback['company_logo'])) {
                    $companyFeedback['company_logo'] = Common::getImgUrl() .
                        convertImageUrlByType($companyFeedback['company_logo'], 'medium');
                }
                $job['feedback'] = $companyFeedback;
            }

            //Get job_times
            $jobTimes = DB::table('job_times')->where('id_job', $job['id'])
                ->orderBy('start_datetime', 'ASC')->get(['start_datetime', 'end_datetime']);
            $job['job_times'] = [];

            if (!$jobTimes->isEmpty()) {
                $jobTimes = $jobTimes->toArray();
                foreach ($jobTimes as $jobTime) {
                    $slot = [
                        'start_datetime' => convertToDateDisplay(strtotime($jobTime->start_datetime), true),
                        'end_datetime' => convertToDateDisplay(strtotime($jobTime->end_datetime), true)
                    ];
                    $job['job_times'][] = $slot;
                }
            }

            $doneJobs[] = $job;
        }
        $listJob['data'] = $doneJobs;

        return $listJob;
    }

    public function update(Request $request)
    {
        //Validate
        $rules = [];
        if ($request->has('id')) {
            $rules['id'] = 'required|numeric';
            $rules['id_package_type'] = 'required|numeric';
            $rules['description'] = 'nullable|string';
            $rules['quantity'] = 'nullable|numeric|min:1';
            $rules['working_address'] = 'nullable|string';
            $rules['job_times'] = 'nullable|array';
            if ($request->has('status')) {
                $status = $request->input('status');
            }
            if ($request->has('job_times')) {
                $rules['job_times.*'] = 'array';
                $rules['job_times.*.*'] = 'date_format:Y-m-d H:i';
            }
        } else {
            $rules['id_package_type'] = 'required|numeric';
            $rules['description'] = 'required|string';
            $rules['quantity'] = 'required|numeric|min:1';
            $rules['working_address'] = 'required|string';
            $rules['job_times'] = 'required|array';
            $rules['job_times.*'] = 'array';
            $rules['job_times.*.*'] = 'date_format:Y-m-d H:i';
        }
        if ($request->has('id_package')) {
            $rules['id_package'] = 'required|numeric';
        }

        $this->validate($request, $rules);

        $err = new Error();
        //Validate id and id_package
        if ($request->has('id')) {
            $id = $request->input('id');
            $myJob = $this->repoJob->find($id);
            if (empty($myJob)) {
                $err->setError('not_found', "No record with id = $id");
                return responseJson($err->getErrors(), 404);
            }
        }
        $repoPackage = new PackageRepo();
        if ($request->has('id_package')) {
            $idPackage = $request->input('id_package');
            $myPackage = $repoPackage->find($idPackage);
            if (empty($myPackage)) {
                $err->setError('not_found', "Not found package with id_package = $idPackage");
                return responseJson($err->getErrors(), 404);
            }
        }

        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "Not found company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $idPackageType = $request->input('id_package_type');
        if (isset($myPackage)) {
            $idPackageType = $myPackage->id_package_type;
        }

        //Create / update job
        $dataUpdated = [];
        if ($request->has('job_times')) {
            $jobTimes = $request->input('job_times');
        } else {
            $jobTimes = DB::table('job_times')->where('id_job', $myJob->id)->get();
            $jobTimes = $jobTimes->toArray();
        }

        $dataUpdated['id_user'] = $loggedUser->id;
        $dataUpdated['id_company'] = $loggedUser->id_company;

        if (is_array($jobTimes[0])) {
            $minStartDateTime = $jobTimes[0]['start_datetime'];
        } else {
            $minStartDateTime = $jobTimes[0]->start_datetime;
        }

        foreach ($jobTimes as $jobTime) {
            if (is_array($jobTime)) {
                $startDateTime = $jobTime['start_datetime'];
            } else {
                $startDateTime = $jobTime->start_datetime;
            }

            if ($minStartDateTime > $startDateTime) {
                $minStartDateTime = $startDateTime;
            }
        }
        $deadline = strtotime($minStartDateTime) - (strtotime($minStartDateTime) - time())*0.1;

        if ($deadline <= time()) {
            $err->setError('deadline_error', "Cannot create job with deadline <= NOW");
            return responseJson($err->getErrors(), 501);
        }

        $dataUpdated['deadline'] = date('Y-m-d H:i:s', $deadline);

        if ($request->has('description')) {
            $dataUpdated['description'] = $request->input('description');
        }
        if ($request->has('quantity')) {
            $dataUpdated['quantity'] = $request->input('quantity');
        }
        if ($request->has('working_address')) {
            $dataUpdated['working_address'] = $request->input('working_address');
        }
        if (isset($status)) {
            $dataUpdated['status'] = $status;
        }

        if (isset($myJob)) {
            $myJob = $this->repoJob->update($myJob->id, $dataUpdated);
        } else {
            $myJob = $this->repoJob->create($dataUpdated);
        }

        //Remove old job_packages with id_job
        DB::table('job_packages')->where('id_job', $myJob->id)->delete();

        //Insert job_packages, compute totalSalary and totalAmount
        $totalAmount = 0;
        $totalSalary = 0;
        if (isset($myPackage)) {
            DB::table('job_packages')->insert([
                'id_job' => $myJob->id,
                'id_package' => $myPackage->id,
                'amount' => $myPackage->price
            ]);
            $totalAmount = $myPackage->price;
            $totalSalary = $myPackage->price*(1 - $myPackage->commission_rate);
        } else {
            $jobTimeByShift = $this->repoJob->getJobTimeByShift($jobTimes);
            $jobPackages = [];
            foreach ($jobTimeByShift as $date => $kinds) {
                foreach ($kinds as $kind) {
                    //Get id_package corresponding to id_package_type & kind
                    $arrFilter['where']['id_package_type'] = $idPackageType;
                    $arrFilter['where']['kind'] = $kind;
                    $arrFilter['no_paging'] = 1;

                    $package = $repoPackage->getPackageList($arrFilter);
                    if ($package->isEmpty()) {
                        $err->setError(
                            'not_found',
                            "Not found package with id_package_type = $idPackageType & kind = $kind"
                        );
                        $this->repoJob->delete($myJob->id);
                        return responseJson($err->getErrors(), 404);
                    }
                    $myPackage = $package[0];

                    if (isset($jobPackages[$myJob->id . '|' . $myPackage->id])) {
                        $jobPackages[$myJob->id . '|' . $myPackage->id]['amount'] += $myPackage->price;
                        $salary = $myPackage->price*(1 - $myPackage->commission_rate);
                        $jobPackages[$myJob->id . '|' . $myPackage->id]['salary'] += $salary;
                    } else {
                        $jobPackages[$myJob->id . '|' . $myPackage->id]['amount'] = $myPackage->price;
                        $salary = $myPackage->price*(1 - $myPackage->commission_rate);
                        $jobPackages[$myJob->id . '|' . $myPackage->id]['salary'] = $salary;
                    }
                }
            }

            foreach ($jobPackages as $key => $jobPackage) {
                list($idJob, $idPackage) = explode('|', $key);

                DB::table('job_packages')->insert([
                    'id_job' => $idJob,
                    'id_package' => $idPackage,
                    'amount' => $jobPackage['amount']
                ]);
                $totalAmount += $jobPackage['amount'];
                $totalSalary += $jobPackage['salary'];
            }
        }
        $myJob = $this->repoJob->update(
            $myJob->id,
            ['total' => $myJob->quantity*$totalAmount, 'salary' => $totalSalary]
        );

        if ($request->has('job_times')) {
            //Remove old job_times with id_job
            DB::table('job_times')->where('id_job', $myJob->id)->delete();

            //Insert new job_times
            foreach ($jobTimes as $jobTime) {
                $jobTime['id_job'] = $myJob->id;
                DB::table('job_times')->insert($jobTime);
            }
        }

        //Update Job to Firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $database->getReference("jobs/{$myJob->id}")->set($myJob);

        return responseJson($myJob);
    }

    public function apply(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric'
        ]);

        $id = $request->input('id');

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found people with logged-in user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $job = $this->repoJob->findWhere([
            ['status', Job::STATUS_ENABLE],
            ['deadline', '>', date('Y-m-d H:i:s')],
            ['id', $id]
        ])->first();

        if (empty($job)) {
            $err->setError('not_found', "No record with id = $id & status = 1 & deadline > NOW");
            return responseJson($err->getErrors(), 404);
        }

        $myJob = $this->repoJob->getJob($id);

        $peopleJob = DB::table('people_jobs')->where('id_people', $loggedUser->id_people)
            ->where('id_job', $myJob->id)->first();

        if (!empty($peopleJob)) {
            $err->setError('error_dupplicated', "Duplicated id_job = $myJob->id, id_people = $loggedUser->id_people");
            return responseJson($err->getErrors(), 501);
        }

        $peopleJobRepo = new PeopleJobRepo();
        //Check if job has job_group and people not satisfied except_worked_period
        if ($myJob->id_job_group > 0 && $peopleJobRepo->checkIfWorkedGroupBefore($loggedUser->id_people, $myJob)) {
            $err->setError(
                'error_worked_group',
                "Candiate used to work the same id_job_group = $myJob->id_job_group".
                " in $myJob->except_worked_period month(s) before"
            );
            return responseJson($err->getErrors(), 501);
        }

        if (!$myJob->is_job_social) {
            $repoPeopleJob = new PeopleJobRepo();
            $checkBusy = $repoPeopleJob->checkIfBusyInTimes($loggedUser->id_people, $myJob->id);
            if ($checkBusy) {
                $err->setError(
                    'duplicated_working_times',
                    "Bạn đã được chọn cho công việc khác, ".
                    "nên không thể ứng tuyển công việc cùng thời gian."
                );
                return responseJson($err->getErrors(), 501);
            }
        }

        DB::table('people_jobs')->insert([
            'id_people' => $loggedUser->id_people,
            'id_job' => $myJob->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        //Build notification data
        $data = [
            'feature' => 'job_recruiting',
            'entity' => 'job',
            'id' => $myJob->id,
            'action' => 'open',
            "extra_info" => ['is_job_social' => $myJob->is_job_social]
        ];

        //Build notification message
        $repoNotiMsg = new NotiMessageRepo();
        $notiMsg = $repoNotiMsg->find('noti_msg_applied_job');
        $message = sprintf($notiMsg->model_msg, $loggedUser->fullname, $myJob->name);

        $repoDevice = new DeviceRepo();
        $repoDevice->pushNotification([$myJob->id_user], $data, $message);

        return responseJson(['applied_success']);
    }

    public function checkin(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric',
            'id_company' => 'required|numeric'
        ]);

        $id = $request->input('id');
        $idCompany = $request->input('id_company');

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found people with logged-in user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        //Check if the job belong to the company
        $myJob = $this->repoJob->findWhere([
            ['id_company', $idCompany],
            ['status', Job::STATUS_ENABLE],
            ['id', $id]
        ])->first();

        if (empty($myJob)) {
            $err->setError('not_found', "No record with id_company = $idCompany, id = $id & status = 1");
            return responseJson($err->getErrors(), 404);
        }

        $peopleJob = DB::table('people_jobs')
            ->where('id_people', $loggedUser->id_people)
            ->where('id_job', $myJob->id)
            ->where('status', 2)->first();

        if (empty($peopleJob)) {
            $err->setError(
                'not_found',
                "Not found id_job = $myJob->id, id_people = $loggedUser->id_people, status = SELECTED"
            );
            return responseJson($err->getErrors(), 404);
        }

        if (!empty($peopleJob->checked_in_at)) {
            $err->setError(
                'duplicated',
                "This candidate already checked in the job on $peopleJob->checked_in_at"
            );
            return responseJson($err->getErrors(), 501);
        }

        $peopleJob = DB::table('people_jobs')
            ->where('id_people', $loggedUser->id_people)
            ->where('id_job', $myJob->id)
            ->update([
                'checked_in_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        //Increase candidate's income
        $repoPeople = new PeopleRepo();
        $repoPeople->update($loggedUser->id_people, ['income' => DB::raw("income + $myJob->salary")]);

        return responseJson(["checked_in_success"]);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric'
        ]);

        $loggedUser = Common::getLoggedUserInfo();
        $err = new Error();
        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "Not found company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $id = $request->input('id');

        $myJob = $this->repoJob->findWhere([
            'id' => $id,
            'id_company' => $loggedUser->id_company
        ])->first();

        if (empty($myJob)) {
            $err->setError('not_found', "Not found job with id = $id and id_company = $loggedUser->id_company");
            return responseJson($err->getErrors(), 404);
        }

        $this->repoJob->deleteJob($id);

        //Remove Job from Firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $database->getReference("jobs/{$myJob->id}")->remove();

        return responseJson(['deleted_success']);
    }
}
