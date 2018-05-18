<?php
namespace App\DataServices\CompanyProject;

interface CompanyProjectRepoInterface
{
    public function getByCompanyId($id_company);
}
