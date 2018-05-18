<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
//Root
$router->get('/', function () use ($router) {
    $result = "Welcome to Canavi API. It's deployed with ".$router->app->version();
    return $result;
});

//API v2
$router->group(['prefix' => 'v2'], function () use ($router) {
    //Middleware Before
    $router->group(['middleware' => 'before'], function () use ($router) {
        //Middleware Auth
        $router->group(['middleware' => 'auth'], function () use ($router) {
            //User
            $router->post('users/connectfacebook', [
                'uses' => 'UserController@connectFacebook', 'as' => 'user_connectfacebook'
            ]);
            $router->get('users/profile', [
                'uses' => 'UserController@profile', 'as' => 'user_profile'
            ]);
            $router->put('users/update', [
                'uses' => 'UserController@update', 'as' => 'user_update'
            ]);

            //People
            $router->put('peoples/update', [
                'uses' => 'PeopleController@update', 'as' => 'people_update'
            ]);
            $router->post('peoples/summary', [
                'uses' => 'PeopleController@getSummary', 'as' => 'people_summary'
            ]);

            //People Feedback
            $router->put('peoplefeedbacks/update', [
                'uses' => 'PeopleFeedbackController@update', 'as' => 'peoplefeedback_update'
            ]);

            //Company
            $router->post('companies/update', [
                'uses' => 'CompanyController@update', 'as' => 'company_update'
            ]);

            //Company Feedback
            $router->put('companyfeedbacks/update', [
                'uses' => 'CompanyFeedbackController@update', 'as' => 'companyfeedback_update'
            ]);

            //File
            $router->post('files/upload', [
                'uses' => 'FileController@post', 'as' => 'file_post'
            ]);
            $router->delete('files/remove', [
                'uses' => 'FileController@delete', 'as' => 'file_delete'
            ]);

            //People Experience
            $router->post('peopleexperiences/update', [
                'uses' => 'PeopleExperienceController@update', 'as' => 'peopleexperience_update'
            ]);
            $router->delete('peopleexperiences/remove', [
                'uses' => 'PeopleExperienceController@delete', 'as' => 'peopleexperience_delete'
            ]);

            //Company Project
            $router->post('companyprojects/update', [
                'uses' => 'CompanyProjectController@update', 'as' => 'companyproject_update'
            ]);
            $router->delete('companyprojects/remove', [
                'uses' => 'CompanyProjectController@delete', 'as' => 'companyproject_delete'
            ]);

            //Job
            //Employer management
            $router->get('jobs/recruiting', [
                'uses' => 'JobController@getRecruitingJobs', 'as' => 'job_recruiting'
            ]);
            $router->get('jobs/history', [
                'uses' => 'JobController@getHistoryJobs', 'as' => 'job_history'
            ]);
            //Candidate management
            $router->post('jobs/calendar', [
                'uses' => 'JobController@getCalendar', 'as' => 'job_calendar'
            ]);
            $router->get('jobs/matching', [
                'uses' => 'JobController@getMatchingJobs', 'as' => 'job_matching'
            ]);
            $router->get('jobs/applied', [
                'uses' => 'JobController@getAppliedJobs', 'as' => 'job_applied'
            ]);
            $router->get('jobs/ongoing', [
                'uses' => 'JobController@getOnGoingJobs', 'as' => 'job_ongoing'
            ]);
            $router->put('jobs/update', [
                'uses' => 'JobController@update', 'as' => 'job_update'
            ]);
            $router->post('jobs/apply', [
                'uses' => 'JobController@apply', 'as' => 'job_apply'
            ]);
            $router->post('jobs/checkin', [
                'uses' => 'JobController@checkin', 'as' => 'job_checkin'
            ]);
            $router->delete('jobs/remove', [
                'uses' => 'JobController@delete', 'as' => 'job_delete'
            ]);
            $router->put('peoplejobs/update', [
                'uses' => 'PeopleJobController@update', 'as' => 'peoplejob_update'
            ]);
            $router->post('peoplejobs/updatepost', [
                'uses' => 'PeopleJobController@updatePost', 'as' => 'peoplejob_updatepost'
            ]);

            //Payment
            $router->put('payments/init', [
                'uses' => 'OrderController@update', 'as' => 'order_update'
            ]);

            //Device
            $router->get('devices/notifications', [
                'uses' => 'DeviceController@getNotifications', 'as' => 'device_notifications'
            ]);
        });

        //User
        $router->post('users/login', [
            'uses' => 'UserController@login', 'as' => 'user_login'
        ]);

        //People
        $router->get('peoples', [
            'uses' => 'PeopleController@getList', 'as' => 'people_list'
        ]);
        $router->get('peoples/{id:[0-9]+}', [
            'uses' => 'PeopleController@getDetail', 'as' => 'people_detail'
        ]);

        //People Experience
        $router->get('peopleexperiences', [
            'uses' => 'PeopleExperienceController@getList', 'as' => 'peopleexperience_list'
        ]);

        //People Feedback
        $router->get('peoplefeedbacks', [
            'uses' => 'PeopleFeedbackController@getList', 'as' => 'peoplefeedback_list'
        ]);

        //Company
        $router->get('companies/{id:[0-9]+}', [
            'uses' => 'CompanyController@getDetail', 'as' => 'company_detail'
        ]);

        //Company Project
        $router->get('companyprojects', [
            'uses' => 'CompanyProjectController@getList', 'as' => 'companyproject_list'
        ]);

        //Company Feedback
        $router->get('companyfeedbacks', [
            'uses' => 'CompanyFeedbackController@getList', 'as' => 'companyfeedback_list'
        ]);

        //Package
        $router->get('packagetypes', [
            'uses' => 'PackageTypeController@getList', 'as' => 'packagetype_list'
        ]);
        $router->get('packagetypes/{id:[0-9]+}', [
            'uses' => 'PackageTypeController@getDetail', 'as' => 'packagetype_detail'
        ]);
        $router->get('packages', [
            'uses' => 'PackageController@getList', 'as' => 'package_list'
        ]);

        //Job
        $router->get('jobs', [
            'uses' => 'JobController@getList', 'as' => 'job_list'
        ]);
        $router->get('jobs/{id:[0-9]+}', [
            'uses' => 'JobController@getDetail', 'as' => 'job_detail'
        ]);

        //Payment
        $router->get('payments/callback', [
            'uses' => 'TransactionController@update', 'as' => 'transaction_update'
        ]);
        $router->get('payments/cancel/{id:[0-9]+}', [
            'uses' => 'TransactionController@cancel', 'as' => 'transaction_cancel'
        ]);

        //People Category
        $router->get('peoplecategories', [
            'uses' => 'PeopleCategoryController@getList', 'as' => 'peoplecategory_list'
        ]);

        //People Media
        $router->get('peoplemedias', [
            'uses' => 'FileController@getList', 'as' => 'peoplemedia_list'
        ]);

        //Region
        $router->get('regions', [
            'uses' => 'RegionController@getList', 'as' => 'region_list'
        ]);

        //Page
        $router->get('pages', [
            'uses' => 'PageController@getList', 'as' => 'page_list'
        ]);
        $router->get('pages/{id:[0-9]+}', [
            'uses' => 'PageController@getDetail', 'as' => 'page_detail'
        ]);
        $router->get('pagecategories/{id:[0-9]+}', [
            'uses' => 'PageController@getChildCategories', 'as' => 'page_category'
        ]);

        //Noti Message
        $router->get('notimessages', [
            'uses' => 'NotiMessageController@getList', 'as' => 'notimessage_list'
        ]);

        //Device
        $router->post('devices/add', [
            'uses' => 'DeviceController@add', 'as' => 'device_add'
        ]);
        $router->delete('devices/remove', [
            'uses' => 'DeviceController@remove', 'as' => 'device_remove'
        ]);
        $router->post('devices/push_notification', [
            'uses' => 'DeviceController@pushNotification', 'as' => 'device_push_notification'
        ]);

        //Test
        $router->get('test/schedule', [
            'uses' => 'TestController@testSchedule', 'as' => 'test_schedule'
        ]);
        $router->get('test/phpinfo', [
            'uses' => 'TestController@testPhpInfo', 'as' => 'test_phpinfo'
        ]);
        $router->get('test/seeding', [
            'uses' => 'TestController@testSeeding', 'as' => 'test_seeding'
        ]);
        $router->delete('test/removejob', [
            'uses' => 'TestController@testRemoveJob', 'as' => 'test_remove_job'
        ]);
    });
});
