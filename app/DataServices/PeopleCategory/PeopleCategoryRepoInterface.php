<?php
namespace App\DataServices\PeopleCategory;

interface PeopleCategoryRepoInterface
{
    public function getPeopleCateList($arrFilter = []);
}
