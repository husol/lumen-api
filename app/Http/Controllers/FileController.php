<?php

namespace App\Http\Controllers;

use App\DataServices\File\FileRepoInterface;
use App\DataServices\People\PeopleRepo;
use Illuminate\Http\Request;
use App\Models\PeopleMedia;
use App\File;
use App\Error;
use App\Common;

class FileController extends Controller
{
    protected $repoFile;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(FileRepoInterface $repoFile)
    {
        $this->repoFile = $repoFile;
    }

    public function getList(Request $request)
    {
        $idPeople = $request->input('id_people');

        $this->validate($request, [
            'id_people' => 'required|numeric'
        ]);

        //Get photos
        $photos = $this->repoFile->getByPeopleId($idPeople);

        $err = new Error();
        if ($photos->isEmpty()) {
            $err->setError('not_found', "No record with id_people = $idPeople");
            return responseJson($err->getErrors(), 404);
        }

        $listMedias = $photos->toArray();

        $medias['photos'] = [];
        foreach ($listMedias as $media) {
            $objMedia = new \stdClass();
            $objMedia->id = $media['id'];
            $objMedia->type = $media['type'];
            $objMedia->file_path = $media['file_path'];
            if (!empty($media['file_path'])) {
                if ($media['type'] == PeopleMedia::TYPE_IMAGE) {
                    $media['file_path'] = convertImageUrlByType($media['file_path'], 'medium');
                }
                $objMedia->file_path = Common::getImgUrl() . $media['file_path'];
            }

            $medias['photos'][] = $objMedia;
        }

        //Get videos
        $repoPeople = new PeopleRepo();
        $myPeople = $repoPeople->find($idPeople);
        $medias['videos'] = explode(',', $myPeople->video_link);

        return responseJson($medias);
    }

    public function post(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:avatar,media',
            'file' => 'required'
        ]);

        $err = new Error();

        if ($request->file('file')->isValid()) {
            $type = $request->input('type');
            $file = $request->file('file');

            switch ($type) {
                case 'avatar':
                    $result = $this->repoFile->uploadAvatar($file);
                    break;
                case 'media':
                    $result = $this->repoFile->uploadPeopleMedia($file);
                    break;
            }

            if (isset($result['path'])) {
                $result['path'] = Common::getImgUrl() . convertImageUrlByType($result['path'], 'medium');
                return responseJson($result);
            }
        }

        $err->setError('file_posted_fail', "File cannot be uploaded");
        return responseJson($err->getErrors(), 501);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:media',
            'id' => 'required|numeric'
        ]);

        $err = new Error();

        $id = $request->input('id');
        switch ($request->input('type')) {
            case 'media':
                $myMedia = $this->repoFile->find($id);
                if (!empty($myMedia)) {
                    if ($myMedia->file_path != '') {
                        $tmpLink = convertImageUrlByType($myMedia->file_path, "small");
                        $fileObj = new File($tmpLink);
                        $fileObj->delete();
                        $tmpLink = convertImageUrlByType($myMedia->file_path, "medium");
                        $fileObj = new File($tmpLink);
                        $fileObj->delete();
                        $fileObj = new File($myMedia->file_path);
                        $fileObj->delete();
                    }
                    if ($this->repoFile->delete($id)) {
                        return responseJson(['file_deleted_success']);
                    } else {
                        $err->setError('file_deleted_fail', 'File cannot be deleted');
                        return responseJson($err->getErrors(), 501);
                    }
                } else {
                    $err->setError('not_found', "No record media with id = $id");
                    return responseJson($err->getErrors(), 404);
                }
                break;
        }

        return responseJson(['file_deleted_fail'], 501);
    }
}
