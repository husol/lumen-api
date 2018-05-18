<?php

namespace App\DataServices\Device;

use App\Common;
use App\DataServices\EloquentRepo;
use App\Firebase;
use App\Models\Device;
use App\Jobs\NotificationJob;
use Illuminate\Support\Facades\Queue;

class DeviceRepo extends EloquentRepo implements DeviceRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Device::class;
    }

    public function getDevicesByUserIds($id_users = [])
    {
        $devices = $this->model->whereIn('id_user', $id_users)->get();

        return $devices;
    }

    public function pushNotification($user_ids, $data, $message)
    {
        $idPlayers = [];
        if ($user_ids == 'all') {
            //Push notification to all devices
            $devices = $this->getAll();
        } else {
            //Push notification to specific devices
            $idUsers = $user_ids;
            $devices = $this->getDevicesByUserIds($idUsers);
        }

        $idUsers = [];
        foreach ($devices as $device) {
            $idPlayers[] = $device->id_player;
            $idUsers[$device->id_user] = 1;
        }

        $dataNotification = $data;
        $notification = new NotificationJob($idPlayers, $dataNotification, $message);

        Queue::push($notification);

        //Update to firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        if (!empty($idUsers)) {
            $userIds = array_keys($idUsers);
            $timeStamp = time();
            $dataNotification['message'] = $message;
            foreach ($userIds as $idUser) {
                $database->getReference("notifications/$idUser/$timeStamp")->set($dataNotification);
            }
        }
    }
}
