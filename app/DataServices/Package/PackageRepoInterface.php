<?php
namespace App\DataServices\Package;

interface PackageRepoInterface
{
    public function getPackageList($arrFilter = []);
}
