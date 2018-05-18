<?php

namespace App\DataServices\Company;

use App\DataServices\EloquentRepo;
use App\DataServices\Job\JobRepo;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use App\Common;

class CompanyRepo extends EloquentRepo implements CompanyRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Company::class;
    }

    public function getByUserId($id_user)
    {
        $company = $this->model->where('id_user', $id_user)
                ->where('status', '>', -1)
                ->first();
        return $company;
    }

    public function getCompanyList($arrFilter = [])
    {
        $selectedFields = [
            'c_id AS id',
            'c_name AS name',
            'c_logo AS logo',
            'c_website AS website',
            'c_email AS email',
            'c_phone AS phone',
            'c_address AS address',
            'avg_rating',
            'c_countview AS countview',
            'c_description AS description'
        ];

        $companies = $this->model->where('status', '>', -1);

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $companies->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $companies->orderBy($field, $value);
            }
        }
        $companies->orderBy('c_id', 'DESC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $companies->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $companies->get($selectedFields);
        }

        return $companies->paginate(Company::PER_PAGE, $selectedFields);
    }

    public function getCompany($id)
    {
        $selectedFields = [
            'c_id AS id',
            'c_name AS name',
            'c_logo AS logo',
            'c_website AS website',
            'c_email AS email',
            'c_phone AS phone',
            'c_address AS address',
            'avg_rating',
            'c_countview AS countview',
            'c_description AS description'
        ];

        $company = $this->model->where('status', '>', -1)->where('id', $id);

        return $company->first($selectedFields);
    }

    public function getRatingAvg($id_company)
    {
        $ratingAvg = DB::table('company_feedback')
            ->where('rating', '>', 0)
            ->where('id_company', $id_company)
            ->avg('rating');

        return $ratingAvg;
    }

    public function getPostedJobTotal($id_company)
    {
        $repoJob = new JobRepo();
        $totalPostedJob = $repoJob->model->where('id_company', $id_company)->count();
        return $totalPostedJob;
    }
}
