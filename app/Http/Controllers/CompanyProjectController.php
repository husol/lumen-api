<?php

namespace App\Http\Controllers;

use App\DataServices\CompanyProject\CompanyProjectRepoInterface;
use Illuminate\Http\Request;
use App\Error;
use App\File;
use App\Common;

class CompanyProjectController extends Controller
{
    protected $repoCompanyProject;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CompanyProjectRepoInterface $repoCompanyProject)
    {
        $this->repoCompanyProject = $repoCompanyProject;
    }

    public function getList(Request $request)
    {
        $idCompany = $request->input('id_company');

        $this->validate($request, [
            'id_company' => 'required|numeric'
        ]);

        //Get projects
        $projects = $this->repoCompanyProject->getByCompanyId($idCompany);

        $err = new Error();
        if ($projects->isEmpty()) {
            return responseJson([]);
        }

        $listProjects = $projects->toArray();

        //Adjust projects info
        foreach ($listProjects as $k => $project) {
            //Adjust logo with S3
            if (!empty($project['logo'])) {
                $listProjects[$k]['logo'] = Common::getImgUrl() . convertImageUrlByType($project['logo'], 'medium');
            }
            //Adjust date format
            $listProjects[$k]['start_date_formatted']
                = convertToDateDisplay(strtotime($project['start_date']));
            if (!empty($project['end_date'])) {
                $listProjects[$k]['end_date_formatted']
                    = convertToDateDisplay(strtotime($project['end_date']));
            }
        }

        return responseJson($listProjects);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'nullable|numeric',
            'title' => 'required',
            'logo' => 'nullable',
            'working_place' => 'required',
            'start_date' => 'required|date_format:d/m/Y',
            'end_date' => 'nullable|date_format:d/m/Y'
        ]);

        $err = new Error();
        $loggedUser = Common::getLoggedUserInfo();

        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "No company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $id = $request->input('id');
        $idCompany = $loggedUser->id_company;
        $title = $request->input('title');
        $workingPlace = $request->input('working_place');
        $startDate = convertToMySqlDate($request->input('start_date'));

        $dataUpdated = [
            'title' => $title,
            'id_company' => $idCompany,
            'working_place' => $workingPlace,
            'start_date' => $startDate,
            'end_date' => null
        ];

        if ($request->has('end_date') && !empty($request->input('end_date'))) {
            $endDate = convertToMySqlDate($request->input('end_date'));
            if ($endDate < $startDate) {
                $err->setError('end_date_invalid', "end_date must be equal or greater than start_date");
                return responseJson($err->getErrors(), 501);
            }
            $dataUpdated['end_date'] = $endDate;
        }

        if (empty($id)) {
            $project = $this->repoCompanyProject->create($dataUpdated);
        } else {
            $project = $this->repoCompanyProject->findWhere(['id_company' => $idCompany, 'id' => $id]);
            if ($project->isEmpty()) {
                $err->setError('not_found', "No record with id_company = $idCompany, id = $id");
                return responseJson($err->getErrors(), 404);
            }
            $project = $this->repoCompanyProject->update($id, $dataUpdated);
        }

        if (empty($project)) {
            $err->setError('error_companyproject_updated', "Record cannot be updated");
            return responseJson($err->getErrors(), 501);
        }

        if ($request->hasFile('logo')) {
            if (!$request->file('logo')->isValid()) {
                $err->setError('error_logo_uploaded', "Logo cannot be uploaded");
                return responseJson($err->getErrors(), 501);
            }
            $logo = $request->file('logo');

            //Start upload logo
            //Delete old logo image
            if (!empty($project->logo)) {
                $tmpLink = convertImageUrlByType($project->logo, "small");
                $fileObj = new File($tmpLink);
                $fileObj->delete();
                $tmpLink = convertImageUrlByType($project->logo, "medium");
                $fileObj = new File($tmpLink);
                $fileObj->delete();
                $fileObj = new File($project->logo);
                $fileObj->delete();
            }

            $sourceFile = ['file'=> $logo->getPathname(), 'name' => $logo->getClientOriginalName()];
            $result = Common::uploadImage($sourceFile);
            if (isset($result['path'])) {
                $project = $this->repoCompanyProject->update($project->id, ['logo' => $result['path']]);
                $project->logo = Common::getImgUrl() . convertImageUrlByType($project->logo, 'medium');
            }
        }

        return $project;
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric'
        ]);

        $loggedUser = Common::getLoggedUserInfo();

        $err = new Error();
        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "No company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $id = $request->input('id');
        $idCompany = $loggedUser->id_company;
        $project = $this->repoCompanyProject->findWhere(['id_company' => $idCompany, 'id' => $id]);
        if ($project->isEmpty()) {
            $err->setError('not_found', "No record with id_company = $idCompany, id = $id");
            return responseJson($err->getErrors(), 404);
        }

        $project = $this->repoCompanyProject->find($id);
        //Delete logo image
        if ($project->logo != "") {
            $tmpLink = convertImageUrlByType($project->logo, "small");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $tmpLink = convertImageUrlByType($project->logo, "medium");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $fileObj = new File($project->logo);
            $fileObj->delete();
        }

        $this->repoCompanyProject->delete($id);

        return responseJson(['deleted_success']);
    }
}
