<?php

namespace App\DataServices\Region;

use App\DataServices\EloquentRepo;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use App\Common;

class RegionRepo extends EloquentRepo implements RegionRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Region::class;
    }

    public function getRegionList($arrFilter = [])
    {
        $selectedFields = [
            'lit_region.r_id AS id',
            'r_parentid AS parent_id',
            'r_name AS name'
        ];

        $regions = $this->model->orderBy('name', 'ASC');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $regions->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $regions->orderBy($field, $value);
            }
        }

        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $regions->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $regions->get($selectedFields);
        }

        return $regions->paginate(Region::PER_PAGE, $selectedFields);
    }
}
