<?php
namespace App\DataServices\CompanyFeedback;

interface CompanyFeedbackRepoInterface
{
    public function getByCompanyId($id_company, $obj);

    public function getByUserCompanyId($id_user, $id_company, $obj);
}
