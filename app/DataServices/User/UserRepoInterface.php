<?php
namespace App\DataServices\User;

interface UserRepoInterface
{
    public function getByEmail($email);

    public function getByPhone($phone);

    public function getAllActive();
}
