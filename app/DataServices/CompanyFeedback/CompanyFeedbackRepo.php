<?php

namespace App\DataServices\CompanyFeedback;

use App\DataServices\EloquentRepo;
use App\Models\CompanyFeedback;

class CompanyFeedbackRepo extends EloquentRepo implements CompanyFeedbackRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return CompanyFeedback::class;
    }

    public function getByCompanyId($id_company, $obj)
    {
        $selectedFields = [
            'company_feedbacks.id',
            'company_feedbacks.id_user',
            'company_feedbacks.id_company',
            'lit_ac_user.u_avatar AS user_avatar',
            'lit_ac_user.u_fullname AS user_name',
            'lit_company.c_logo AS company_logo',
            'lit_company.c_name AS company_name',
            'content'
        ];

        if ($obj->type == 'job') {
            $selectedFields[] = 'rating';
        }

        $companyFeedbacks = $this->model->join('lit_ac_user', 'lit_ac_user.u_id', '=', 'company_feedbacks.id_user')
            ->join('lit_company', 'lit_company.c_id', '=', 'company_feedbacks.id_company')
            ->where('company_feedbacks.id_company', $id_company)
            ->where('object_type', $obj->type)
            ->where('object_id', $obj->id)
            ->orderBy('company_feedbacks.created_at', 'DESC')
            ->get($selectedFields);

        return $companyFeedbacks;
    }

    public function getByUserCompanyId($id_user, $id_company, $obj)
    {
        $selectedFields = [
            'company_feedbacks.id',
            'company_feedbacks.id_user',
            'company_feedbacks.id_company',
            'lit_ac_user.u_avatar AS user_avatar',
            'lit_ac_user.u_fullname AS user_name',
            'lit_company.c_logo AS company_logo',
            'lit_company.c_name AS company_name',
            'content'
        ];

        if ($obj->type == 'job') {
            $selectedFields[] = 'rating';
        }

        $companyFeedbacks = $this->model->join('lit_ac_user', 'lit_ac_user.u_id', '=', 'company_feedbacks.id_user')
            ->join('lit_company', 'lit_company.c_id', '=', 'company_feedbacks.id_company')
            ->where('company_feedbacks.id_user', $id_user)
            ->where('company_feedbacks.id_company', $id_company)
            ->where('object_type', $obj->type)
            ->where('object_id', $obj->id)
            ->orderBy('company_feedbacks.created_at', 'DESC')
            ->get($selectedFields);

        return $companyFeedbacks;
    }
}
