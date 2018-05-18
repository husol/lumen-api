<?php
/**
 * Description of Firebase
 *
 * @author khoaht
 */
namespace App;

use Kreait\Firebase\Factory;

class Firebase extends Factory
{
    public function __construct()
    {
        $firebaseCredentials = env('FIREBASE_CREDENTIALS');
        $firebaseUrl = env('FIREBASE_CONFIG_URL');
        if (!file_exists($firebaseCredentials) && !empty($firebaseUrl)) {
            $firebaseConfig = file_get_contents($firebaseUrl);
            file_put_contents($firebaseCredentials, $firebaseConfig);
        }
        return (new Factory)->create();
    }
}
