<?php

namespace App\DataServices\Package;

use App\DataServices\EloquentRepo;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use App\Common;

class PackageRepo extends EloquentRepo implements PackageRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Package::class;
    }

    public function getPackageList($arrFilter = [])
    {
        $selectedFields = [
            'packages.id',
            'id_package_type',
            'package_types.name AS package_type_name',
            'packages.name AS package_name',
            'kind',
            'target_follow',
            'target_like',
            'price',
            'commission_rate'
        ];

        $packages = $this->model->join('package_types', 'package_types.id', '=', 'packages.id_package_type');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $packages->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $packages->orderBy($field, $value);
            }
        }
        $packages->orderBy('packages.order', 'ASC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $packages->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $packages->get($selectedFields);
        }

        return $packages->paginate(Package::PER_PAGE, $selectedFields);
    }
}
