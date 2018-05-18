<?php

namespace App\Http\Controllers;

use App\DataServices\People\PeopleRepoInterface;
use App\DataServices\File\FileRepo;
use App\DataServices\PeopleFeedback\PeopleFeedbackRepo;
use App\DataServices\PeopleJob\PeopleJobRepo;
use App\Error;
use App\Models\PeopleMedia;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use App\Models\PeopleCategory;
use Illuminate\Http\Request;
use App\Common;

class PeopleController extends Controller
{
    protected $repoPeople;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PeopleRepoInterface $repoPeople)
    {
        $this->repoPeople = $repoPeople;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        $isFeatured = $request->input('is_featured');
        if ($isFeatured == 1) {
            $arrFilter['where']['is_featured'] = 1;
        }

        if ($request->has('id_job')) {
            $this->validate($request, [
                'id_job' => 'required|numeric',
                'type' => 'required|in:applied,selected'
            ]);
            $idJob = $request->input('id_job');
            $arrFilter['where']['id_job'] = $idJob;
            $peopleJobStatus = 1;
            if ($request->has('type') && $request->input('type') == 'selected') {
                $arrFilter['no_paging'] = 1;
                $peopleJobStatus = 2;
            }
            $arrFilter['people_job_status'] = $peopleJobStatus;
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

        $peoples = $this->repoPeople->getPeopleList($arrFilter);

        $err = new Error();
        if (empty($peoples)) {
            return responseJson([]);
        }

        $listPeople = $peoples->toArray();

        $dataPeople = $listPeople;
        if (isset($listPeople['data'])) {
            $dataPeople = $listPeople['data'];
        }

        $repoPeopleFeedback = new PeopleFeedbackRepo();
        //Adjust people info
        foreach ($dataPeople as $k => $people) {
            //For avatar
            if (!empty($people['cover'])) {
                $dataPeople[$k]['avatar'] = $people['cover'];
            }
            if (!empty($dataPeople[$k]['avatar'])) {
                $dataPeople[$k]['avatar'] = Common::getImgUrl() .
                    convertImageUrlByType($dataPeople[$k]['avatar'], 'medium');
            }
            unset($dataPeople[$k]['cover']);

            $dataPeople[$k]['avg_rating'] = floatval($dataPeople[$k]['avg_rating']);
            $dataPeople[$k]['avg_rating_rounded'] = round($dataPeople[$k]['avg_rating']*2)/2;
            $commentTotal = $this->repoPeople->getCommentTotal($people['id']);
            $dataPeople[$k]['comment_total'] = intval($commentTotal);
            $doneJobTotal = $this->repoPeople->getDoneJobTotal($people['id']);
            $dataPeople[$k]['done_job_total'] = intval($doneJobTotal);
            $categories = [];
            $categoriesName = [];
            for ($i = 1; $i <= 5; $i++) {
                unset($dataPeople[$k]['cate'.$i]);

                if ($people['cate'.$i] == 0) {
                    continue;
                }
                $myCategory = PeopleCategory::where('id', $people['cate'.$i])
                    ->select('id', 'name')->first();
                $categoriesName[] = $myCategory->name;
                $categories[] = $myCategory;
            }
            $dataPeople[$k]['categories_string'] = implode(', ', $categoriesName);
            $dataPeople[$k]['categories'] = $categories;

            if (isset($idJob) && $request->has('type') && $request->input('type') == 'selected') {
                $dataPeople[$k]['rating'] = 0;
                //Get rating if any
                $obj = new \stdClass();
                $obj->type = 'job';
                $obj->id = $idJob;
                $peopleFeedbacks = $repoPeopleFeedback->getByPeopleId($people['id'], $obj);

                if (!$peopleFeedbacks->isEmpty()) {
                    $peopleFeedbacks = $peopleFeedbacks->toArray();
                    $peopleFeedback = $peopleFeedbacks[0];
                    $dataPeople[$k]['rating'] = $peopleFeedback['rating'];
                }
                //Get people job post if any
                $peopleJobPost = DB::table('people_job_posts')
                        ->where('id_people', $people['id'])
                        ->where('id_job', $idJob)
                        ->first(['fb_id_post', 'countlike', 'countshare', 'countcomment']);
                if (!is_null($peopleJobPost)) {
                    $dataPeople[$k]['fb_id_post'] = $peopleJobPost->fb_id_post;
                    $dataPeople[$k]['countlike'] = $peopleJobPost->countlike;
                    $dataPeople[$k]['countshare'] = $peopleJobPost->countshare;
                    $dataPeople[$k]['countcomment'] = $peopleJobPost->countcomment;
                }
            }
        }

        if (isset($listPeople['data'])) {
            $listPeople['data'] = $dataPeople;
            return responseJson($listPeople);
        }

        return responseJson($dataPeople);
    }

    public function getDetail(Request $request, $id)
    {
        $err = new Error();
        $people = $this->repoPeople->getPeople($id);
        if (is_null($people)) {
            $err->setError('not_found', "Not found candidate with id = $id");
            return responseJson($err->getErrors(), 404);
        }
        $objPeople = (object) $people->toArray();

        if ($request->has('id_job')) {
            $this->validate($request, [
                'id_job' => 'required|numeric'
            ]);
            $idJob = $request->input('id_job');

            $repoPeopleJob = new PeopleJobRepo();
            $peopleJobs = $repoPeopleJob->findWhere([
                'id_people' => $objPeople->id,
                'id_job' => $idJob,
                ['status', '>', 0]
            ]);

            if ($peopleJobs->isEmpty()) {
                $err->setError(
                    'not_found',
                    "Not found people job with id_people = $objPeople->id & id_job = $idJob & status > 0"
                );
                return responseJson($err->getErrors(), 404);
            }

            $peopleJob = $peopleJobs[0];
            if ($peopleJob['status'] == 2) {
                $obj = new \stdClass();
                $obj->type = 'job';
                $obj->id = $idJob;
                $repoPeopleFeedback = new PeopleFeedbackRepo();
                $peopleFeedbacks = $repoPeopleFeedback->getByPeopleId($objPeople->id, $obj);
                $objPeople->rating = 0;
                if (!$peopleFeedbacks->isEmpty()) {
                    $peopleJob['rating'] = $peopleFeedbacks[0]['pf_rating'];
                }
            }
            $objPeople->people_job = $peopleJob;
        }

        //Adjust dataPeople info
        //For avatar
        if (!empty($objPeople->cover)) {
            DB::table('lit_ac_user')->where('u_id', $objPeople->id_user)->update(['u_avatar' => $objPeople->cover]);
            $this->repoPeople->update($objPeople->id, ['cover' => '']);
            $objPeople->avatar = $objPeople->cover;
        }

        if (!empty($objPeople->avatar)) {
            $objPeople->avatar = Common::getImgUrl() . convertImageUrlByType($objPeople->avatar, 'medium');
        }
        unset($objPeople->cover);
        //For age
        $objPeople->age = intval(date("Y")) - intval($objPeople->birth_year);

        //For avg_rating
        $objPeople->avg_rating = floatval($objPeople->avg_rating);
        $objPeople->avg_rating_rounded = round($objPeople->avg_rating*2)/2;

        //For regions
        $objPeople->region = Region::where('id', $objPeople->id_region)
            ->select('id', 'parent_id', 'name')->first();
        unset($objPeople->id_region);
        $objPeople->sub_region = Region::where('id', $objPeople->id_subregion)
            ->select('id', 'parent_id', 'name')->first();
        unset($objPeople->id_subregion);

        $objPeople->region_string = '';
        if ($objPeople->sub_region) {
            $objPeople->region_string .= $objPeople->sub_region->name;
        }
        if ($objPeople->region) {
            $objPeople->region_string .= (empty($objPeople->region_string) ? "" : ", ")
                . $objPeople->region->name;
        }

        //For share_link
        $tmpFullname = codau2khongdau($objPeople->fullname, true);
        $objPeople->share_link = env('WEB_ROOTURL')."/candidate/{$tmpFullname}-$objPeople->id";

        //For categories
        $categories = [];
        $categoriesName = [];
        for ($i = 1; $i <= 5; $i++) {
            $cate = 'cate'.$i;
            if ($objPeople->$cate == 0) {
                unset($objPeople->$cate);
                continue;
            }
            $myCategory = PeopleCategory::where('id', $objPeople->$cate)
                ->select('id', 'name')->first();
            $categoriesName[] = $myCategory->name;
            $categories[] = $myCategory;
            unset($objPeople->$cate);
        }
        $objPeople->categories_string = implode(', ', $categoriesName);
        $objPeople->categories = $categories;
        //For medias
        $repoFile = new FileRepo();
        $medias = $repoFile->getByPeopleId($objPeople->id);
        $listMedias = $medias->toArray();

        $objPeople->medias = [];
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

            $objPeople->medias[] = $objMedia;
        }

        //Increase countview
        $this->repoPeople->update($objPeople->id, ['countview' => ++$objPeople->countview]);

        return responseJson($objPeople);
    }

    public function update(Request $request)
    {
        $loggedUser = Common::getLoggedUserInfo();

        $dataUpdated = [];
        if ($request->has('birth_year')) {
            $dataUpdated['birth_year'] = $request->input('birth_year');
        }
        if ($request->has('gender')) {
            $dataUpdated['gender'] = $request->input('gender');
        }
        if ($request->has('id_region')) {
            $dataUpdated['id_region'] = $request->input('id_region');
        }
        if ($request->has('id_subregion')) {
            $dataUpdated['id_subregion'] = $request->input('id_subregion');
        }
        if ($request->has('cate1')) {
            $dataUpdated['cate1'] = $request->input('cate1');
        }
        if ($request->has('cate2')) {
            $dataUpdated['cate2'] = $request->input('cate2');
        }
        if ($request->has('cate3')) {
            $dataUpdated['cate3'] = $request->input('cate3');
        }
        if ($request->has('cate4')) {
            $dataUpdated['cate4'] = $request->input('cate4');
        }
        if ($request->has('cate5')) {
            $dataUpdated['cate5'] = $request->input('cate5');
        }
        if ($request->has('video_link')) {
            $dataUpdated['video_link'] = $request->input('video_link');
        }

        $myPeople = $this->repoPeople->getByUserId($loggedUser->id);

        if (empty($myPeople)) {
            $dataUpdated['id_user'] = $loggedUser->id;
            $people = $this->repoPeople->create($dataUpdated);
        } else {
            $people = $this->repoPeople->update($myPeople->id, $dataUpdated);
        }

        return responseJson($people);
    }

    public function getSummary(Request $request)
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

        $result = [];
        //Get avg_rating
        $result['avg_rating'] = $this->repoPeople->getRatingAvg($idPeople);

        //Get Done Jobs by month
        $yearMonth = $request->input('year_month');
        $monthlyDoneJobs = $this->repoPeople->getDoneJobsByMonth($idPeople, $yearMonth);
        $result['done_job_total'] = count($monthlyDoneJobs);

        $result['salary_total'] = 0;
        foreach ($monthlyDoneJobs as $job) {
            $result['salary_total'] += $job->salary;
        }

        $result['done_jobs'] = $monthlyDoneJobs;

        return responseJson($result);
    }
}
