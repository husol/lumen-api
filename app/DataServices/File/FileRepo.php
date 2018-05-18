<?php

namespace App\DataServices\File;

use App\DataServices\EloquentRepo;
use App\DataServices\User\UserRepo;
use App\DataServices\People\PeopleRepo;
use App\Models\People;
use App\Models\PeopleMedia;
use Illuminate\Support\Facades\DB;
use App\File;
use App\Common;

class FileRepo extends EloquentRepo implements FileRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PeopleMedia::class;
    }

    public function getByPeopleId($id_people)
    {
        $selectedFields = [
            'pm_id AS id',
            'u_id AS id_user',
            'p_id AS id_people',
            'pm_type AS type',
            'pm_description AS caption',
            'pm_filepath AS file_path',
            'pm_countview AS countview'
        ];
        $medias = $this->model->where('id_people', $id_people)->get($selectedFields);
        return $medias;
    }

    public function uploadAvatar($file)
    {
        $loggedUser = Common::getLoggedUserInfo();

        //Delete old avatar image
        if ($loggedUser->avatar != "") {
            $tmpLink = convertImageUrlByType($loggedUser->avatar, "small");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $tmpLink = convertImageUrlByType($loggedUser->avatar, "medium");
            $fileObj = new File($tmpLink);
            $fileObj->delete();
            $fileObj = new File($loggedUser->avatar);
            $fileObj->delete();
        }

        $sourceFile = ['file'=> $file->getPathname(), 'name' => $file->getClientOriginalName()];
        $prefix = codau2khongdau($loggedUser->fullname, true);

        $result = Common::uploadImage($sourceFile, 'avatar', $prefix);

        if ($result['error'] == 0) {
            DB::table('lit_ac_user')
                ->where('u_id', $loggedUser->id)
                ->update(['u_avatar' => $result['path']]);
        }

        return $result;
    }

    public function uploadPeopleMedia($file)
    {
        $loggedUser = Common::getLoggedUserInfo();

        $repoPeople = new PeopleRepo();
        $myPeople = $repoPeople->getByUserId($loggedUser->id);

        if (is_null($myPeople)) {
            $myPeople = $repoPeople->create(['id_user' => $loggedUser->id, 'status' => People::STATUS_DISABLED]);
        }

        if ($myPeople->id > 0) {
            $sourceFile = ['file'=> $file->getPathname(), 'name' => $file->getClientOriginalName()];
            $prefix = codau2khongdau($loggedUser->fullname, true);
            $result = Common::uploadImage($sourceFile, 'images', $prefix);

            if ($result['error'] == 0) {
                $this->model->create([
                    'id_user' => $loggedUser->id,
                    'id_people' => $myPeople->id,
                    'type' => PeopleMedia::TYPE_IMAGE,
                    'file_path' => $result['path']
                ]);
            }
        } else {
            $result['error'] = 501;
            $result['info'] = 'error_people_init';
        }

        return $result;
    }
}
