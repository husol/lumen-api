<?php

namespace App\Http\Controllers;

use App\Common;
use App\DataServices\Device\DeviceRepoInterface;
use App\Firebase;
use App\Error;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $repoDevice;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(DeviceRepoInterface $repoDevice)
    {
        $this->repoDevice = $repoDevice;
    }

    public function add(Request $request)
    {
        $this->validate($request, [
            'id_player' => 'required|size:36',
            'id_user' => 'required',
            'model' => 'required',
            'platform' => 'required',
            'language' => 'required|size:2'
        ]);

        //Add device on DB
        $device = $this->repoDevice->firstOrNew(['id_player' => $request->input("id_player")]);
        $device->id_user = $request->input("id_user");
        $device->model = $request->input("model");
        $device->platform = $request->input("platform");
        $device->language = $request->input("language");
        $device->tags = $request->input("tags");
        $device->save();

        return responseJson(['device_added']);
    }

    public function remove(Request $request)
    {
        $this->validate($request, [
            'id_player' => 'required|size:36'
        ]);

        $id_player = $request->input('id_player');
        $result = $this->repoDevice->delete($id_player);
        $err = new Error();
        if ($result) {
            return responseJson(['device_removed']);
        }
        $err->setError('device_not_found', "Device $id_player is not found");
        return responseJson($err->getErrors(), 404);
    }

    public function pushNotification(Request $request)
    {
        $this->validate($request, [
            'user_ids' => 'required',
            'data' => 'required',
            'message' => 'required'
        ]);

        $userIds = $request->input('user_ids');
        $data = $request->input('data');
        $message = $request->input('message');
        $this->repoDevice->pushNotification($userIds, $data, $message);

        return responseJson(['notification_pushed']);
    }

    public function getNotifications(Request $request)
    {
        $loggedUser = Common::getLoggedUserInfo();
        //Get notifications from firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();
        $notifications = $database->getReference("notifications/$loggedUser->id")->getValue();

        if (empty($notifications)) {
            return responseJson([]);
        }

        krsort($notifications, SORT_NUMERIC);
        $notifications = array_values($notifications);

        return responseJson($notifications);
    }
}
