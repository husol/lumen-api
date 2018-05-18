<?php

namespace App\Http\Controllers;

use App\DataServices\CompanyFeedback\CompanyFeedbackRepoInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class CompanyFeedbackController extends Controller
{
    protected $repoCompanyFeedback;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CompanyFeedbackRepoInterface $repoCompanyFeedback)
    {
        $this->repoCompanyFeedback = $repoCompanyFeedback;
    }

    public function getList(Request $request)
    {
        $this->validate($request, [
            'id_company' => 'required|numeric',
            'object_type' => 'required|in:job,none'
        ]);

        $idCompany = $request->input('id_company');
        $obj = new \stdClass();
        $obj->type = $request->input('object_type');
        $obj->id = 0;
        if ($obj->type == 'job') {
            $this->validate($request, [
                'object_id' => 'required|numeric'
            ]);
            $obj->id = $request->input('object_id');
        }

        $companyFeedbacks = $this->repoCompanyFeedback->getByCompanyId($idCompany, $obj);

        if ($companyFeedbacks->isEmpty()) {
            return responseJson([]);
        }

        foreach ($companyFeedbacks as $k => $companyFeedback) {
            if (!empty($companyFeedback['user_avatar'])) {
                $companyFeedbacks[$k]['user_avatar'] = Common::getImgUrl() . $companyFeedback['user_avatar'];
            }
            if (!empty($companyFeedback['company_logo'])) {
                $companyFeedbacks[$k]['company_logo'] = Common::getImgUrl() . $companyFeedback['company_logo'];
            }
        }

        return responseJson($companyFeedbacks);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_company' => 'required|numeric',
            'content' => 'nullable|string'
        ]);

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();

        $idCompany = $request->input('id_company');
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

            //Check if the people worked on the job
            if (empty($loggedUser->id_people)) {
                $err->setError('not_found', "Not found id_people with logged-in user id = $loggedUser->id");
                return responseJson($err->getErrors(), 404);
            }

            $peopleJobs = DB::table('people_jobs')->where([
                'id_people' => $loggedUser->id_people,
                'id_job' => $obj->id,
                'status' => 2,
                ['checked_in_at', '<>', '0000-00-00 00:00:00']
            ])->whereNotNull('checked_in_at')->get();

            if ($peopleJobs->isEmpty()) {
                $err->setError('not_found', "Candidate $loggedUser->id_people didn't work on the job $obj->id");
                return responseJson($err->getErrors(), 501);
            }
        }

        if ($obj->type == 'job') {
            //Update company feedback
            $myCompanyFeedback = $this->repoCompanyFeedback->firstOrNew([
                'id_user' => $loggedUser->id,
                'id_company' => $idCompany,
                'object_type' => $obj->type,
                'object_id' => $obj->id
            ]);
            $myCompanyFeedback->rating = $rating;
            $myCompanyFeedback->content = $content;

            $myCompanyFeedback->save();
        } else {
            $dataUpdated = [
                'id_user' => $loggedUser->id,
                'id_company' => $idCompany,
                'object_type' => $obj->type,
                'object_id' => $obj->id,
                'rating' => $rating,
                'content' => $content
            ];
            if (isset($id)) {
                $myCompanyFeedback = $this->repoCompanyFeedback->find($id);
                if (is_null($myCompanyFeedback)) {
                    $err->setError('not_found', "Not found company feedback id = $id");
                    return responseJson($err->getErrors(), 404);
                }
                $this->repoCompanyFeedback->update($id, $dataUpdated);
            } else {
                $this->repoCompanyFeedback->create($dataUpdated);
            }
        }

        return responseJson(['updated_success']);
    }
}
