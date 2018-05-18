<?php
namespace App\DataServices\PeopleJob;

interface PeopleJobRepoInterface
{
    public function getPeopleJob($id_people, $id_job);

    public function getByPeopleId($id_people, $where = []);

    public function getByJobId($id_job, $where = []);

    public function checkIfBusyInTimes($id_people, $id_job);

    public function checkIfWorkedGroupBefore($id_people, $jobObj);
}
