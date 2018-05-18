<?php

namespace App\DataServices\Page;

use App\DataServices\EloquentRepo;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use App\Common;

class PageRepo extends EloquentRepo implements PageRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Page::class;
    }

    public function getPageList($arrFilter = [])
    {
        $selectedFields = [
            'lit_page.pg_id AS id',
            'lit_page.pc_id AS id_page_category',
            'lit_page_language.pgl_name AS title',
            'lit_page.pg_image AS cover',
            'lit_page_language.pgl_slug AS slug',
            'lit_page.pg_countview AS countview',
            'lit_page_language.pgl_content AS content',
            'lit_page.pg_datecreated AS date_created',
            'lit_page.created_at',
        ];

        $pages = $this->model->join('lit_page_language', 'lit_page.pg_id', '=', 'lit_page_language.pg_id')
                        ->where('lit_page.pg_type', Page::TYPE_NEWS);

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $pages->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $pages->orderBy($field, $value);
            }
        }
        $pages->orderBy('lit_page.pg_countview', 'DESC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $pages->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $pages->get($selectedFields);
        }

        return $pages->paginate(Page::PER_PAGE, $selectedFields);
    }

    public function getPage($id)
    {
        $selectedFields = [
            'lit_page.pg_id AS id',
            'lit_page.pc_id AS id_page_category',
            'lit_page_language.pgl_name AS title',
            'lit_page.pg_image AS cover',
            'lit_page_language.pgl_slug AS slug',
            'lit_page.pg_countview AS countview',
            'lit_page_language.pgl_content AS content',
            'lit_page.pg_datecreated AS date_created',
            'lit_page.created_at',
        ];

        $page = $this->model->join('lit_page_language', 'lit_page.pg_id', '=', 'lit_page_language.pg_id')
            ->where('lit_page.pg_type', Page::TYPE_NEWS)
            ->where('lit_page.pg_id', $id)->first($selectedFields);

        return $page;
    }

    public function getCategoryList($parent_id)
    {
        $selectedFields = [
            'lit_page_category.pc_id AS id',
            'lit_page_category_language.pcl_name AS title',
            'lit_page_category.pc_image AS cover',
            'lit_page_category_language.pcl_slug AS slug',
        ];

        $pageCateList = DB::table('lit_page_category')
                    ->join('lit_page_category_language', 'lit_page_category.pc_id', 'lit_page_category_language.pc_id')
                    ->where('lit_page_category.pc_parentid', $parent_id)
                    ->where('lit_page_category.pc_type', Page::TYPE_NEWS)
                    ->where('lit_page_category.pc_status', 1)
                    ->orderBy('lit_page_category.pc_displayorder', 'ASC')
                    ->get($selectedFields);

        return $pageCateList;
    }
}
