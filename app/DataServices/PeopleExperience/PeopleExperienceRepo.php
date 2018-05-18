<?php

namespace App\DataServices\PeopleExperience;

use App\DataServices\EloquentRepo;
use App\Models\PeopleExperience;

class PeopleExperienceRepo extends EloquentRepo implements PeopleExperienceRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return PeopleExperience::class;
    }

    public function getByPeopleId($id_people)
    {
        $selectedFields = [
            'id',
            'title',
            'logo',
            'working_place',
            'start_date',
            'end_date'
        ];
        $experiences = $this->model->where('id_people', $id_people)
            ->orderBy('start_date', 'DESC')
            ->get($selectedFields);
        return $experiences;
    }
}
