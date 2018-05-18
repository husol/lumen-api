<?php

namespace App\Providers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\ServiceProvider;
use App\Common;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.
        $this->app['auth']->viaRequest('api', function ($request) {
            try {
                if (!$user = JWTAuth::parseToken()->authenticate()) {
                    return null;
                }
            } catch (Exception $e) {
                return null;
            }
            return $user;
        });
    }
}
