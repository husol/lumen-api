<?php

namespace App\DataServices\PeopleFeedback;

use App\DataServices\EloquentRepo;
use App\Models\PeopleFeedback;
use App\Common;

class PeopleFeedbackRepo extends EloquentRepo implements PeopleFeedbackRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PeopleFeedback::class;
    }

    public function getByPeopleId($id_people, $obj)
    {
        $selectedFields = [
            'pf_id AS id',
            'lit_people_feedback.u_id AS id_user',
            'a.u_avatar AS user_avatar',
            'a.u_fullname AS user_name',
            'b.u_avatar AS candidate_avatar',
            'b.u_fullname AS candidate_name',
            'pf_content AS content',
            'lit_people_feedback.created_at'
        ];

        if ($obj->type == 'job') {
            $selectedFields[] = 'pf_rating AS rating';
        }

        $peopleFeedbacks = $this->model->join('lit_ac_user AS a', 'a.u_id', '=', 'lit_people_feedback.u_id')
                ->join('lit_people', 'lit_people_feedback.p_id', '=', 'lit_people.p_id')
                ->join('lit_ac_user AS b', 'b.u_id', '=', 'lit_people.u_id')
                ->where('lit_people_feedback.p_id', $id_people)
                ->where('object_type', $obj->type);
        if ($obj->type != "job_social") {
            $peopleFeedbacks = $peopleFeedbacks->where('object_id', $obj->id);
        }
        $peopleFeedbacks = $peopleFeedbacks->orderBy('lit_people_feedback.created_at', 'DESC')
                ->get($selectedFields);

        return $peopleFeedbacks;
    }
}
