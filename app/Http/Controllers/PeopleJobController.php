<?php

namespace App\Http\Controllers;

use App\DataServices\Device\DeviceRepo;
use App\DataServices\Job\JobRepo;
use App\DataServices\NotiMessage\NotiMessageRepo;
use App\DataServices\People\PeopleRepo;
use App\DataServices\PeopleJob\PeopleJobRepo;
use App\DataServices\PeopleJob\PeopleJobRepoInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class PeopleJobController extends Controller
{
    protected $repoPeopleJob;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PeopleJobRepoInterface $repoPeopleJob)
    {
        $this->repoPeopleJob = $repoPeopleJob;
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_people' => 'required|numeric',
            'id_job' => 'required|numeric',
            'status' => 'required|numeric|in:0,1,2'
        ]);

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();

        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "No company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $idPeople = $request->input('id_people');
        $idJob = $request->input('id_job');

        //Check if the job belong to the company
        $repoJob = new JobRepo();
        $jobs = $repoJob->findWhere([
            'id' => $idJob,
            'id_company' => $loggedUser->id_company
        ]);

        if ($jobs->isEmpty()) {
            $err->setError('not_found', "No job with id_company = $loggedUser->id_company & id = $idJob");
            return responseJson($err->getErrors(), 404);
        }

        $status = $request->input('status');

        $peopleJob = $this->repoPeopleJob->findWhere(['id_people' => $idPeople, 'id_job' => $idJob]);
        if ($peopleJob->isEmpty()) {
            $err->setError('not_found', "No record with id_people = $idPeople, id_job = $idJob");
            return responseJson($err->getErrors(), 404);
        }

        $myJob = $repoJob->getJob($idJob);
        //Check if candidate busy
        if ($status == 2 && !$myJob->is_job_social) {
            $repoPeopleJob = new PeopleJobRepo();
            $checkBusy = $repoPeopleJob->checkIfBusyInTimes($idPeople, $idJob);
            if ($checkBusy) {
                $err->setError(
                    'duplicated_working_times',
                    "Ứng viên này đã được chọn trước cho công việc khác. ".
                    "Bạn hãy chuyển sang chọn ứng viên kế tiếp."
                );
                return responseJson($err->getErrors(), 501);
            }
        }

        $whereCondition = [
            ['id_people', $idPeople],
            ['id_job', $idJob]
        ];
        $dataUpdated = [
            'status' => $status
        ];
        $peopleJob = $this->repoPeopleJob->updateWhere($whereCondition, $dataUpdated);

        if (!$peopleJob) {
            $err->setError('error_peoplejob_updated', "Record cannot be updated");
            return responseJson($err->getErrors(), 501);
        }

        //Push notification if candidate is selected
        if ($status == 2) {
            $repoPeople = new PeopleRepo();
            $people = $repoPeople->getPeople($idPeople);
            $people = $people->toArray();
            //Build notification data
            $data = [
                'feature' => 'job',
                'entity' => 'job',
                'id' => $myJob->id,
                'action' => 'open',
                "extra_info" => ['is_job_social' => $myJob->is_job_social]
            ];

            //Build notification message
            $repoNotiMsg = new NotiMessageRepo();
            $notiMsg = $repoNotiMsg->find('noti_msg_selected_candidate');
            $message = sprintf($notiMsg->model_msg, $people['fullname'], $myJob->name);

            $repoDevice = new DeviceRepo();
            $repoDevice->pushNotification([$people['id_user']], $data, $message);
        }

        return responseJson(['updated_success']);
    }

    public function updatePost(Request $request)
    {
        $this->validate($request, [
            'id_job' => 'required|numeric',
            'fb_id_post' => 'required|string'
        ]);

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "Not found people with logged-in user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $idJob = $request->input('id_job');

        $repoPeopleJob = new PeopleJobRepo();

        //Check if the job is mine
        $peopleJob = DB::table('people_jobs')
            ->where('id_people', $loggedUser->id_people)
            ->where('id_job', $idJob)
            ->where('status', 2)->first();

        if (empty($peopleJob)) {
            $err->setError(
                'not_found',
                "Not found people job with id_people = $loggedUser->id_people, id_job = $idJob & status = 2"
            );
            return responseJson($err->getErrors(), 404);
        }

        $fbIdPost = $request->input('fb_id_post');

        $peopleJobPost = DB::table('people_job_posts')
            ->updateOrInsert([
                'id_people' => $loggedUser->id_people,
                'id_job' => $idJob
            ], [
                'id_user' => $loggedUser->id,
                'fb_id_post' => $fbIdPost,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return responseJson(['posted_success']);
    }
}
