<?php

namespace App\DataServices\User;

use App\Common;
use App\DataServices\EloquentRepo;
use App\DataServices\People\PeopleRepo;
use Illuminate\Support\Facades\DB;

class UserRepo extends EloquentRepo implements UserRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return \App\Models\User::class;
    }

    public function getByEmail($email)
    {
        return $this->model->where('email', $email)->where('status', '>', -1)->first();
    }

    public function getByPhone($phone)
    {
        $user = $this->model->where('phone', $phone)->where('status', '>', -1)->first();
        if (is_null($user)) {
            $repoPeople = new PeopleRepo();
            $people = $repoPeople->model->where('phone', $phone)->where('status', '>', -1)->first();

            if (!is_null($people)) {
                $this->update($people->id_user, ['phone' => $people->phone]);
                $user = $this->model->where('id', $people->id_user)->where('status', '>', -1)->first();
            }
        }

        return $user;
    }

    public function getAllActive()
    {
        $result = $this->model->where('status > ', 1)->get();

        return $result;
    }
}
