<?php

namespace App\Http\Controllers;

use App\DataServices\PeopleExperience\PeopleExperienceRepoInterface;
use Illuminate\Http\Request;
use App\Error;
use App\File;
use App\Common;

class PeopleExperienceController extends Controller
{
    protected $repoPeopleExp;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PeopleExperienceRepoInterface $repoPeopleExperience)
    {
        $this->repoPeopleExp = $repoPeopleExperience;
    }

    public function getList(Request $request)
    {
        $idPeople = $request->input('id_people');

        $this->validate($request, [
            'id_people' => 'required|numeric'
        ]);

        //Get experiences
        $experiences = $this->repoPeopleExp->getByPeopleId($idPeople);

        $err = new Error();
        if ($experiences->isEmpty()) {
            return responseJson([]);
        }

        $listExperiences = $experiences->toArray();

        //Adjust experiences info
        foreach ($listExperiences as $k => $experience) {
            //Adjust logo with S3
            if (!empty($experience['logo'])) {
                $listExperiences[$k]['logo'] = Common::getImgUrl() . $experience['logo'];
            }
            //Adjust date format
            $listExperiences[$k]['start_date_formatted']
                = convertToDateDisplay(strtotime($experience['start_date']));
            if (!empty($experience['end_date'])) {
                $listExperiences[$k]['end_date_formatted']
                    = convertToDateDisplay(strtotime($experience['end_date']));
            }
        }

        return responseJson($listExperiences);
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

        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "No people with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $id = $request->input('id');
        $idPeople = $loggedUser->id_people;
        $title = $request->input('title');
        $workingPlace = $request->input('working_place');
        $startDate = convertToMySqlDate($request->input('start_date'));

        $dataUpdated = [
            'title' => $title,
            'id_people' => $idPeople,
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
            $experience = $this->repoPeopleExp->create($dataUpdated);
        } else {
            $experience = $this->repoPeopleExp->findWhere(['id_people' => $idPeople, 'id' => $id]);
            if ($experience->isEmpty()) {
                $err->setError('not_found', "No record with id_people = $idPeople, id = $id");
                return responseJson($err->getErrors(), 404);
            }
            $experience = $this->repoPeopleExp->update($id, $dataUpdated);
        }

        if (empty($experience)) {
            $err->setError('error_peopleexperience_updated', "Record cannot be updated");
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
            if (!empty($experience->logo)) {
                $tmpLink = convertImageUrlByType($experience->logo, "small");
                $fileObj = new File($tmpLink);
                $fileObj->delete();
                $tmpLink = convertImageUrlByType($experience->logo, "medium");
                $fileObj = new File($tmpLink);
                $fileObj->delete();
                $fileObj = new File($experience->logo);
                $fileObj->delete();
            }

            $sourceFile = ['file'=> $logo->getPathname(), 'name' => $logo->getClientOriginalName()];
            $result = Common::uploadImage($sourceFile);
            if (isset($result['path'])) {
                $experience = $this->repoPeopleExp->update($experience->id, ['logo' => $result['path']]);
                $experience->logo = Common::getImgUrl() . convertImageUrlByType($experience->logo, 'medium');
            }
        }

        return $experience;
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric'
        ]);

        $loggedUser = Common::getLoggedUserInfo();

        $err = new Error();
        if (empty($loggedUser->id_people)) {
            $err->setError('not_found', "No people with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $id = $request->input('id');
        $idPeople = $loggedUser->id_people;
        $experience = $this->repoPeopleExp->findWhere(['id_people' => $idPeople, 'id' => $id]);
        if ($experience->isEmpty()) {
            $err->setError('not_found', "No record with id_people = $idPeople, id = $id");
            return responseJson($err->getErrors(), 404);
        }

        $experience = $this->repoPeopleExp->find($id);
        //Delete logo image
        if ($experience->logo != "") {
            $tmpLink = convertImageUrlByType($experience->logo, "small");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $tmpLink = convertImageUrlByType($experience->logo, "medium");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $fileObj = new File($experience->logo);
            $fileObj->delete();
        }

        $this->repoPeopleExp->delete($id);

        return responseJson(['deleted_success']);
    }
}
