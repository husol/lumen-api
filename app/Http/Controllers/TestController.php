<?php

namespace App\Http\Controllers;

use App\Common;
use App\Error;
use Illuminate\Http\Request;
use App\Firebase;
use Illuminate\Support\Facades\Artisan;

class TestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function testSchedule(Request $request)
    {
        //Get notifications from firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();
        $schedule = $database->getReference("tests/test_schedule")->getValue();

        return responseJson("Schedule run on $schedule");
    }

    public function testPhpInfo(Request $request)
    {
        phpinfo();

        die;
    }

    public function testSeeding(Request $request)
    {
        Artisan::call('db:seed');

        die("Finished Seeding.");
    }

    public function testRemoveJob(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric',
            'username' =>'required|in:canavi',
            'password' =>'required|in:canavi0209'
        ]);

        $id = $request->input('id');

//        $myJob = $this->repoJob->findWhere([
//            'id' => $id
//        ])->first();

//        $err = new Error();
//        if (empty($myJob)) {
//            $err->setError('not_found', "Not found job with id = $id");
//            return responseJson($err->getErrors(), 404);
//        }
//
//        $this->repoJob->deleteJob($id);

        //Remove Job from Firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $database->getReference("jobs/{$id}")->remove();

        return responseJson(['deleted_success']);
    }
}
