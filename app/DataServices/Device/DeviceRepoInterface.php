<?php
namespace App\DataServices\Device;

interface DeviceRepoInterface
{
    public function getDevicesByUserIds($id_users = []);
}
