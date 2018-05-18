<?php

namespace App\DataServices\PackageType;

use App\DataServices\EloquentRepo;
use App\Models\PackageType;
use Illuminate\Support\Facades\DB;
use App\Common;

class PackageTypeRepo extends EloquentRepo implements PackageTypeRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PackageType::class;
    }

    public function getPackageTypeList($arrFilter = [])
    {
        $selectedFields = [
            'package_types.id',
            'id_package_category',
            'pc.name AS package_category_name',
            'is_job_social',
            'package_types.name AS package_type_name',
            'cover',
            'description'
        ];

        $packageTypes = $this->model->join('package_categories AS pc', 'pc.id', '=', 'id_package_category');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $packageTypes->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $packageTypes->orderBy($field, $value);
            }
        }
        $packageTypes->orderBy('pc.order', 'ASC');
        $packageTypes->orderBy('package_types.order', 'ASC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $packageTypes->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $packageTypes->get($selectedFields);
        }

        return $packageTypes->paginate(PackageType::PER_PAGE, $selectedFields);
    }

    public function getPackageType($id)
    {
        $selectedFields = [
            'package_types.id',
            'id_package_category',
            'is_job_social',
            'package_types.name',
            'cover',
            'description',
        ];

        $packageType = $this->model
            ->join('package_categories AS pc', 'pc.id', '=', 'package_types.id_package_category')
            ->where('package_types.id', $id)->first($selectedFields);

        return $packageType;
    }

    public function getPackageTypeByJobId($id_job)
    {
        $selectedFields = [
            'package_types.id',
            'package_types.name',
            'pc.is_job_social'
        ];

        $packageType = $this->model
            ->join('packages AS p', 'p.id_package_type', '=', 'package_types.id')
            ->join('package_categories AS pc', 'package_types.id_package_category', '=', 'pc.id')
            ->join('job_packages AS jp', function ($join) use ($id_job) {
                $join->on('p.id', '=', 'jp.id_package');
                $join->where('jp.id_job', '=', $id_job);
            })->groupBy('package_types.id')->first($selectedFields);

        return $packageType;
    }
}
