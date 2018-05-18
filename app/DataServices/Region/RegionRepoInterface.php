<?php
namespace App\DataServices\Region;

interface RegionRepoInterface
{
    public function getRegionList($arrFilter = []);
}
