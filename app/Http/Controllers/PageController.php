<?php

namespace App\Http\Controllers;

use App\DataServices\Page\PageRepoInterface;
use App\DataServices\PageRepo;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class PageController extends Controller
{
    protected $repoPage;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PageRepoInterface $repoPage)
    {
        $this->repoPage = $repoPage;
    }

    public function getList(Request $request)
    {
        $arrFilter = [];
        //Filter if any
        $isFeatured = $request->input('is_featured');
        if ($isFeatured == 1) {
            $arrFilter['where']['is_featured'] = 1;
        }

        //Filter page by page category id
        $isCategoryId = $request->input('id_page_category');
        if ($isCategoryId) {
            $arrFilter['where']['id_page_category'] = $isCategoryId;
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

        $pages = $this->repoPage->getPageList($arrFilter);
        $listPage = $pages->toArray();

        $dataPages = $listPage;
        if (isset($listPage['data'])) {
            $dataPages = $listPage['data'];
        }

        foreach ($dataPages as $k => $page) {
            //Format if necessary
            if (!empty($page['cover'])) {
                $dataPages[$k]['cover'] = Common::getImgUrl() . convertImageUrlByType($page['cover'], 'medium');
            } else {
                $dataPages[$k]['cover'] = '';
            }
            if (!empty($page['created_at'])) {
                $dataPages[$k]['created_at_formatted'] = convertToDateDisplay(strtotime($page['created_at']));
            } else {
                $dataPages[$k]['created_at_formatted'] = convertToDateDisplay($page['date_created']);
            }
            unset($dataPages[$k]['created_at']);
            unset($dataPages[$k]['date_created']);

            $dataPages[$k]['content'] = stringCatContent($page['content'], 100);
        }

        if (isset($listPage['data'])) {
            $listPage['data'] = $dataPages;
            return responseJson($listPage);
        }

        return responseJson($dataPages);
    }

    public function getDetail(Request $request, $id)
    {
        $err = new Error();
        $page = $this->repoPage->getPage($id);
        if (is_null($page)) {
            $err->setError('not_found', "Not found page with id = $id");
            return responseJson($err->getErrors(), 404);
        }

        $dataPage = $page->toArray();

        //Adjust page info
        if (!empty($dataPage['cover'])) {
            $dataPage['cover'] = Common::getImgUrl() . convertImageUrlByType($dataPage['cover'], 'medium');
        }
        if (!empty($page['created_at'])) {
            $dataPage['created_at_formatted'] = convertToDateDisplay(strtotime($dataPage['created_at']));
        } else {
            $dataPage['created_at_formatted'] = convertToDateDisplay($dataPage['date_created']);
        }
        unset($dataPage['created_at']);
        unset($dataPage['date_created']);

        return responseJson($dataPage);
    }

    public function getChildCategories($parent_id)
    {
        $categores = $this->repoPage->getCategoryList($parent_id);
        $listCategory = $categores->toArray();

        foreach ($listCategory as $k => $cate) {
            //Format if necessary
            if (!empty($cate->cover)) {
                $listCategory[$k]->cover = Common::getImgUrl() . convertImageUrlByType($cate->cover, 'medium');
            }
        }

        return responseJson($listCategory);
    }
}
