<?php
namespace App\DataServices\PeopleFeedback;

interface PeopleFeedbackRepoInterface
{
    public function getByPeopleId($id_people, $obj);
}
