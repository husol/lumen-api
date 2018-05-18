<?php
namespace App\DataServices\File;

interface FileRepoInterface
{
    public function getByPeopleId($id_people);

    public function uploadAvatar($file);

    public function uploadPeopleMedia($file);
}
