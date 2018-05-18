<?php

namespace App\DataServices\CompanyProject;

use App\DataServices\EloquentRepo;
use App\Models\CompanyProject;

class CompanyProjectRepo extends EloquentRepo implements CompanyProjectRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return CompanyProject::class;
    }

    public function getByCompanyId($id_company)
    {
        $selectedFields = [
            'id',
            'title',
            'logo',
            'working_place',
            'start_date',
            'end_date'
        ];
        $projects = $this->model->where('id_company', $id_company)
            ->orderBy('start_date', 'DESC')
            ->get($selectedFields);
        return $projects;
    }
}
