<?php

namespace App\Http\Controllers;

use App\DataServices\Job\JobRepo;
use App\DataServices\People\PeopleRepo;
use App\DataServices\PeopleFeedback\PeopleFeedbackRepoInterface;
use App\Models\PeopleFeedback;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class PeopleFeedbackController extends Controller
{
    protected $repoPeopleFeedback;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PeopleFeedbackRepoInterface $repoPeopleFeedback)
    {
        $this->repoPeopleFeedback = $repoPeopleFeedback;
    }

    public function getList(Request $request)
    {
        $this->validate($request, [
            'id_people' => 'required|numeric',
            'object_type' => 'required|in:job,none'
        ]);

        $idPeople = $request->input('id_people');
        $obj = new \stdClass();
        $obj->type = $request->input('object_type');
        $obj->id = 0;
        if ($obj->type == 'job') {
            $this->validate($request, [
                'object_id' => 'required|numeric'
            ]);
            $obj->id = $request->input('object_id');
        }

        $peopleFeedbacks = $this->repoPeopleFeedback->getByPeopleId($idPeople, $obj);
        if ($obj->type == "none") {
            $obj->type = 'job_social';
        }
        $peopleFeedbacksMore = $this->repoPeopleFeedback->getByPeopleId($idPeople, $obj);

        $peopleFeedbacks = array_merge($peopleFeedbacks->toArray(), $peopleFeedbacksMore->toArray());
        if (empty($peopleFeedbacks)) {
            return responseJson([]);
        }

        $repoPeople = new PeopleRepo();
        foreach ($peopleFeedbacks as $k => $peopleFeedback) {
            //Get id_people of id_user if any
            $people = $repoPeople->getByUserId($peopleFeedback['id_user']);
            $peopleFeedbacks[$k]['id_people'] = 0;
            if (!empty($people)) {
                $peopleFeedbacks[$k]['id_people'] = $people->p_id;
            }
            if (!empty($peopleFeedback['user_avatar'])) {
                $peopleFeedbacks[$k]['user_avatar'] = Common::getImgUrl() . $peopleFeedback['user_avatar'];
            }
            if (!empty($peopleFeedback['candidate_avatar'])) {
                $peopleFeedbacks[$k]['candidate_avatar'] = Common::getImgUrl() . $peopleFeedback['candidate_avatar'];
            }
        }

        if ($obj->type == 'job') {
            return responseJson($peopleFeedbacks[0]);
        }

        return responseJson($peopleFeedbacks);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_people' => 'required|numeric',
            'content' => 'nullable|string'
        ]);

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();

        $idPeople = $request->input('id_people');
        $content = $request->input('content');
        $obj = new \stdClass();
        $obj->type = 'none';
        $obj->id = 0;
        $rating = 0;

        if ($request->has('id')) {
            $this->validate($request, [
                'id' => 'required|numeric'
            ]);
            $id = $request->input('id');
        }

        if ($request->has('rating')) {
            $this->validate($request, [
                'id_job' => 'required|numeric',
                'rating' => 'required|numeric|between:1,5'
            ]);
            $obj->type = 'job';
            $obj->id = $request->input('id_job');
            $rating = $request->input('rating');

            //Check if the job belong to logged-in user
            if (empty($loggedUser->id_company)) {
                $err->setError('not_found', "Not found id_company with logged-in user id = $loggedUser->id");
                return responseJson($err->getErrors(), 404);
            }

            $repoJob = new JobRepo();
            $jobs = $repoJob->findWhere([
                'id' => $obj->id,
                'id_company' => $loggedUser->id_company
            ]);

            if ($jobs->isEmpty()) {
                $err->setError('not_found', "Not found id_company with logged-in user id = $loggedUser->id");
                return responseJson($err->getErrors(), 404);
            }
        }

        if ($obj->type == 'job') {
            //Update people feedback
            $myPeopleFeedback = $this->repoPeopleFeedback->firstOrNew([
                'u_id' => $loggedUser->id,
                'p_id' => $idPeople,
                'object_type' => $obj->type,
                'object_id' => $obj->id,
                'pf_status' => PeopleFeedback::STATUS_ENABLE
            ]);
            $myPeopleFeedback->rating = $rating;
            $myPeopleFeedback->content = $content;

            $myPeopleFeedback->save();
        } else {
            $dataUpdated = [
                'id_user' => $loggedUser->id,
                'id_people' => $idPeople,
                'object_type' => $obj->type,
                'object_id' => $obj->id,
                'rating' => $rating,
                'content' => $content,
                'status' => PeopleFeedback::STATUS_ENABLE,
            ];
            if (isset($id)) {
                $myPeopleFeedback = $this->repoPeopleFeedback->find($id);
                if (is_null($myPeopleFeedback)) {
                    $err->setError('not_found', "Not found people feedback id = $id");
                    return responseJson($err->getErrors(), 404);
                }
                $this->repoPeopleFeedback->update($id, $dataUpdated);
            } else {
                $this->repoPeopleFeedback->create($dataUpdated);
            }
        }

        //We need to compute people's avg_rating after add/update/delete people_feedback
        $repoPeople = new PeopleRepo();
        $avgRating = $repoPeople->getRatingAvg($idPeople);
        $repoPeople->update($idPeople, ['avg_rating' => $avgRating]);

        return responseJson(['updated_success']);
    }
}
