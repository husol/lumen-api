<?php
namespace App\DataServices\PackageType;

interface PackageTypeRepoInterface
{
    public function getPackageTypeList($arrFilter = []);

    public function getPackageType($id);

    public function getPackageTypeByJobId($id_job);
}
