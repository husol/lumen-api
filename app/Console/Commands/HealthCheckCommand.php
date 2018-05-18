<?php

namespace App\Console\Commands;

use App\Firebase;
use App\Jobs\QueueHealthJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class HealthCheckCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "Health:check";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check to make sure the queue and schedule are running";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Start health check.');
        //Update latest_schedule to firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();
        $database->getReference("health/latest_schedule")->set(date('r', time()));

        $queueHealthUpdate = new QueueHealthJob();
        Queue::push($queueHealthUpdate);

        $this->info('End health check.');
        return true;
    }
}
