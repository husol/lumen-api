<?php

namespace App\Http\Controllers;

use App\DataServices\Package\PackageRepo;
use App\DataServices\PackageType\PackageTypeRepoInterface;
use App\Models\PackageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\File;
use App\Error;
use App\Common;

class PackageTypeController extends Controller
{
    protected $repoPackageType;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PackageTypeRepoInterface $repoPackageType)
    {
        $this->repoPackageType = $repoPackageType;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        $arrFilter['no_paging'] = 1;

        $sortBy = $request->input('sort_by');
        $sortType = $request->input('sort_type');
        $limit = $request->input('limit');
        if (!empty($sortBy)) {
            $arrFilter['order'][$sortBy] = empty($sortType) ? 'DESC' : $sortType;
        }
        if (!empty($limit)) {
            $arrFilter['limit'] = $limit;
        }

        $arrFilter['where']['status'] = PackageType::STATUS_ENABLE;

        $packageTypes = $this->repoPackageType->getPackageTypeList($arrFilter);

        $listPackageType = [];
        foreach ($packageTypes as $packageType) {
            $listPackageType[$packageType->id_package_category]['pkg_cate'] = $packageType->package_category_name;
            if (!empty($packageType->cover)) {
                $packageType->cover = Common::getImgUrl() . $packageType->cover;
            }
            $listPackageType[$packageType->id_package_category]['pkg_types'][] = $packageType;
        }

        $listPackageType = array_values($listPackageType);

        return responseJson($listPackageType);
    }

    public function getDetail(Request $request, $id)
    {
        $err = new Error();
        $packageType = $this->repoPackageType->getPackageType($id);
        if (is_null($packageType)) {
            $err->setError('not_found', "Not found package type with id = $id");
            return responseJson($err->getErrors(), 404);
        }

        $dataPackageType = $packageType->toArray();

        //Adjust page info
        if (!empty($dataPackageType['cover'])) {
            $dataPackageType['cover'] = Common::getImgUrl().$dataPackageType['cover'];
        }

        $repoPackage = new PackageRepo();
        $arrFilter['where']['id_package_type'] = $packageType['id'];
        $arrFilter['no_paging'] = 1;
        $packages = $repoPackage->getPackageList($arrFilter);

        //Adjust package data info
        $packagesStr = '';
        if (!$packages->isEmpty()) {
            $packages = $packages->toArray();
            foreach ($packages as $k => $package) {
                $packages[$k]['post_quantity'] = 1;
                $packages[$k]['target_follow'] = intval($package['target_follow']);
                $packages[$k]['target_like'] = intval($package['target_like']);
                $packages[$k]['price'] = floatval($package['price']);
                $packages[$k]['commission_rate'] = floatval($package['commission_rate']);
                switch ($package['kind']) {
                    case "check_in":
                        $packages[$k]['kind'] = 'Check in';
                        break;
                    case "live_stream":
                        $packages[$k]['kind'] = 'Live stream';
                        break;
                }
                $priceVND = formatNumber($package['price']*1000);
                $packagesStr .= "<li>{$package['package_name']}: <b>{$priceVND} VNĐ</b></li>";
            }
        }

        $dataPackageType['packages'] = $packages;

        //Adjust package type description and notice
        if ($dataPackageType['is_job_social']) {
            $dataPackageType['notice'] = "<ul><li>Nội dung, hình ảnh sẽ được lên bởi ứng viên, ".
                "và đưa khách hàng duyệt để lên bài</li>".
                "<li>Ứng viên từ chối uống các chất kích thích (Rượu, bia)</li></ul>";
        } else {
            //Also build packages string in description
            $dataPackageType['description'] .= "<br><ul>$packagesStr</ul><br><b>Chú ý:</b><ul>".
                "<li>Ca tối bắt đầu sau 17.00</li>".
                "<li>Ứng viên chỉ làm trong khu vực Thành Phố Hồ Chí Minh</li>".
                "<li>Khách hàng tự chuẩn bị trang phục cho ứng viên hoặc ".
                "ứng viên tự chuẩn bị trang phục (Quần Jean, ".
                "chân váy cơ bản, áo thun đơn giản, đồ công sở lịch sự)</li>".
                "<li>Ứng viên từ chối uống các chất kích thích (Rượu, bia)</li>".
                "<li>Đến nơi làm việc trước 30 phút để được hướng dẫn về công việc.</li>".
                "</ul>";
        }

        return responseJson($dataPackageType);
    }
}
