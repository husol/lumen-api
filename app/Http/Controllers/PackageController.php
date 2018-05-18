<?php

namespace App\Http\Controllers;

use App\DataServices\Package\PackageRepoInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\File;
use App\Error;
use App\Common;

class PackageController extends Controller
{
    protected $repoPackage;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PackageRepoInterface $repoPackage)
    {
        $this->repoPackage = $repoPackage;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        if ($request->has('id_package_type')) {
            $idPackageType = $request->input('id_package_type');
            $arrFilter['where']['id_package_type'] = $idPackageType;
            $arrFilter['no_paging'] = 1;
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

        $packages = $this->repoPackage->getPackageList($arrFilter);

        $listPackage = $packages->toArray();

        $dataPackage = $listPackage;
        if (isset($listPackage['data'])) {
            $dataPackage = $listPackage['data'];
        }

        //Adjust package data
        foreach ($dataPackage as $k => $package) {
            $dataPackage[$k]["post_quantity"] = 1;
            $dataPackage[$k]["target_follow"] = intval($dataPackage[$k]["target_follow"]);
            $dataPackage[$k]["target_like"] = intval($dataPackage[$k]["target_like"]);
            $dataPackage[$k]['price'] = floatval($dataPackage[$k]['price']);
            switch ($package['kind']) {
                case "check_in":
                    $dataPackage[$k]['kind'] = 'Check in';
                    break;
                case "live_stream":
                    $dataPackage[$k]['kind'] = 'Live stream';
                    break;
            }
        }

        if (isset($listPackage['data'])) {
            $listPackage['data'] = $dataPackage;
            return responseJson($listPackage);
        }

        return responseJson($dataPackage);
    }
}
