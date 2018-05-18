<?php

namespace App\Http\Controllers;

use App\DataServices\PeopleCategory\PeopleCategoryRepoInterface;
use Illuminate\Http\Request;
use App\Common;

class PeopleCategoryController extends Controller
{
    protected $repoPeopleCate;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PeopleCategoryRepoInterface $repoPeopleCate)
    {
        $this->repoPeopleCate = $repoPeopleCate;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        $sortBy = $request->input('sort_by');
        $sortType = $request->input('sort_type');
        $limit = $request->input('limit');

        $arrFilter['where']['parent_id'] = 0;

        if (!empty($sortBy)) {
            $arrFilter['order'][$sortBy] = empty($sortType) ? 'DESC' : $sortType;
        }
        if (!empty($limit)) {
            $arrFilter['limit'] = $limit;
        }

        $peopleCates = $this->repoPeopleCate->getPeopleCateList($arrFilter);

        $listPeopleCate = $peopleCates->toArray();

        $dataPeopleCate = $listPeopleCate;
        if (isset($listPeopleCate['data'])) {
            $dataPeopleCate = $listPeopleCate['data'];
        }

        //Include people cate children info
        foreach ($dataPeopleCate as $k => $cate) {
            $arrFilter = [];
            $arrFilter['no_paging'] = 1;
            $arrFilter['where']['parent_id'] = $cate['id'];
            $dataPeopleCate[$k]['childs'] = $this->repoPeopleCate->getPeopleCateList($arrFilter);
        }

        return responseJson($dataPeopleCate);
    }
}
