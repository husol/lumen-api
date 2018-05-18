<?php
namespace App\DataServices\Page;

interface PageRepoInterface
{
    public function getPageList($arrFilter = []);
    public function getPage($id);
    public function getCategoryList($parent_id);
}
