<?php
namespace App\DataServices\Job;

interface JobRepoInterface
{
    public function getJobList($arrFilter = []);

    public function getJob($id);

    public function getAppliedPeopleJobs($loggedUser);

    public function getOnGoingPeopleJobs($loggedUser);

    public function getDonePeopleJobs($id_people);

    public function getJobTimeByShift($job_times);

    public function getCalendarMonthOfPeople($id_people, $year_month);

    public function deleteJob($id);
}
