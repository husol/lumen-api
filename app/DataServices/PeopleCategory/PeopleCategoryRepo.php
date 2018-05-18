<?php

namespace App\DataServices\PeopleCategory;

use App\DataServices\EloquentRepo;
use App\Models\PeopleCategory;

class PeopleCategoryRepo extends EloquentRepo implements PeopleCategoryRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PeopleCategory::class;
    }

    public function getPeopleCateList($arrFilter = [])
    {
        $selectedFields = [
            'lit_people_category.pc_id AS id',
            'pc_parentid AS parent_id',
            'pc_name AS name',
            'pc_slug AS slug',

        ];

        $peopleCates = $this->model->orderBy('pc_displayorder', 'ASC');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $peopleCates->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $peopleCates->orderBy($field, $value);
            }
        }

        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $peopleCates->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $peopleCates->get($selectedFields);
        }

        return $peopleCates->paginate(PeopleCategory::PER_PAGE, $selectedFields);
    }
}
