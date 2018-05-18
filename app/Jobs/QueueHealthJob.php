<?php

namespace App\Jobs;
use App\Firebase;

class QueueHealthJob extends Job
{
    public function updateFirebaseQueueHealth()
    {
        //Update test_schedule to firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();
        $database->getReference("health/latest_queue")->set(date('r', time()));
        return true;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = $this->updateFirebaseQueueHealth();
    }
}
