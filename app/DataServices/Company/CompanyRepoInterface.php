<?php
namespace App\DataServices\Company;

interface CompanyRepoInterface
{
    public function getByUserId($id_user);

    public function getCompanyList($arrFilter = []);

    public function getCompany($id);

    public function getRatingAvg($id_company);

    public function getPostedJobTotal($id_company);
}
