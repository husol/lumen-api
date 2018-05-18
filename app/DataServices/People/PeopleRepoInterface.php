<?php
namespace App\DataServices\People;

interface PeopleRepoInterface
{
    public function getByUserId($id_user);

    public function getPeopleList($arrFilter = []);

    public function getPeople($id);

    public function getRatingAvg($id_people);

    public function getCommentTotal($id_people);

    public function getDoneJobTotal($id_people);

    public function getDoneJobsByMonth($id_people, $year_month);
}
