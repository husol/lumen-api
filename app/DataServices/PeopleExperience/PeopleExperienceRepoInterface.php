<?php
namespace App\DataServices\PeopleExperience;

interface PeopleExperienceRepoInterface
{
    public function getByPeopleId($id_people);
}
