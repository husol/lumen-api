<?php

namespace App\DataServices\People;

use App\DataServices\EloquentRepo;
use App\Models\People;
use Illuminate\Support\Facades\DB;
use App\Common;

class PeopleRepo extends EloquentRepo implements PeopleRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return People::class;
    }

    public function getByUserId($id_user)
    {
        $people = $this->model->where('id_user', $id_user)
                ->where('status', '>', -1)
                ->first();
        return $people;
    }

    public function getPeopleList($arrFilter = [])
    {
        $selectedFields = [
            'lit_people.p_id AS id',
            'lit_ac_user.u_fullname AS fullname',
            'lit_ac_user.u_avatar AS avatar',
            'avg_rating',
            'p_coverimage AS cover',
            'p_cat1 AS cate1',
            'p_cat2 AS cate2',
            'p_cat3 AS cate3',
            'p_cat4 AS cate4',
            'p_cat5 AS cate5',
            'p_countview AS countview',
            'p_countlike AS countlike'
        ];

        $peoples = $this->model->join('lit_ac_user', 'lit_people.u_id', '=', 'lit_ac_user.u_id');
        if (isset($arrFilter['people_job_status'])) {
            if ($arrFilter['people_job_status'] == 2) {
                $peoples->join('lit_ac_user_profile AS up', 'up.u_id', '=', 'lit_people.u_id');
                $selectedFields[] = 'up.up_phone AS user_phone';
            }
            $selectedFields[] = 'checked_in_at';
            $peoples->join('people_jobs', 'lit_people.p_id', '=', 'people_jobs.id_people')
                ->where('people_jobs.status', $arrFilter['people_job_status']);
        }
        $peoples->where('lit_ac_user.u_status', '>', -1)->where('status', '>', -1);

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $peoples->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $peoples->orderBy($field, $value);
            }
        }
        $peoples->orderBy('lit_people.p_id', 'DESC');
        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $peoples->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $peoples->get($selectedFields);
        }

        return $peoples->paginate(People::PER_PAGE, $selectedFields);
    }

    public function getPeople($id)
    {
        $selectedFields = [
            'lit_people.p_id AS id',
            'lit_people.u_id AS id_user',
            'lit_ac_user.u_fullname AS fullname',
            'lit_ac_user.u_avatar AS avatar',
            'avg_rating',
            'p_coverimage AS cover',
            'p_gioitinh AS gender',
            'p_namsinh AS birth_year',
            'p_countview AS countview',
            'p_countlike AS countlike',
            'p_videolink AS video_link',
            'p_region AS id_region',
            'p_subregion AS id_subregion',
            'p_cat1 AS cate1',
            'p_cat2 AS cate2',
            'p_cat3 AS cate3',
            'p_cat4 AS cate4',
            'p_cat5 AS cate5'
        ];

        $people = $this->model->join('lit_ac_user', 'lit_people.u_id', '=', 'lit_ac_user.u_id')
            ->where('lit_ac_user.u_status', '>', -1)
            ->where('status', '>', -1)
            ->where('id', $id)
            ->first($selectedFields);

        return $people;
    }

    public function getRatingAvg($id_people)
    {
        $ratingAvg = DB::table('lit_people_feedback')
            ->where('pf_rating', '>', 0)
            ->where('pf_status', '=', 1)
            ->where('p_id', '=', $id_people)
            ->avg('pf_rating');

        return $ratingAvg;
    }

    public function getCommentTotal($id_people)
    {
        $totalComment = DB::table('lit_people_feedback')
            ->where('pf_status', '=', 1)
            ->where('p_id', $id_people)
            ->count();

        return $totalComment;
    }

    public function getDoneJobTotal($id_people)
    {
        $totalDoneJob = DB::table('people_jobs')
            ->where('id_people', $id_people)
            ->where('status', 2)
            ->where('checked_in_at', '<>', '0000-00-00 00:00:00')
            ->whereNotNull('checked_in_at')
            ->count();
        return $totalDoneJob;
    }

    public function getDoneJobsByMonth($id_people, $year_month)
    {
        $sql = "SELECT jobs.id, lit_company.c_name AS company_name, package_types.name AS package_type_name,
                    package_categories.is_job_social, salary,
                    people_jobs.checked_in_at, pjp.status AS job_post_status
                FROM people_jobs LEFT JOIN jobs ON jobs.id = people_jobs.id_job
                    JOIN lit_company ON lit_company.c_id = jobs.id_company
                    LEFT JOIN people_job_posts AS pjp
                        ON people_jobs.id_people = pjp.id_people AND people_jobs.id_job = pjp.id_job
                    JOIN job_packages ON job_packages.id_job = jobs.id
                    JOIN packages ON packages.id = job_packages.id_package
                    JOIN package_types ON package_types.id = packages.id_package_type
                    JOIN package_categories ON package_categories.id = package_types.id_package_category
                WHERE people_jobs.status = 2 AND people_jobs.id_people = $id_people
                    AND DATE_FORMAT(jobs.deadline, '%Y-%m') = '$year_month'
                GROUP BY jobs.id
                HAVING checked_in_at IS NOT NULL OR job_post_status = 1";

        $doneJobs = DB::select($sql);

        return $doneJobs;
    }
}
