<?php

namespace App\Http\Controllers;

use App\DataServices\Region\RegionRepoInterface;
use Illuminate\Http\Request;
use App\Common;

class RegionController extends Controller
{
    protected $repoRegion;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(RegionRepoInterface $repoRegion)
    {
        $this->repoRegion = $repoRegion;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        $parentId = intval($request->input('parent_id'));
        if ($parentId > 0) {
            $arrFilter['where']['parent_id'] = $parentId;
        } else {
            $arrFilter['where']['level'] = 1;
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

        $regions = $this->repoRegion->getRegionList($arrFilter);

        return responseJson($regions);
    }
}
