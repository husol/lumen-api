<?php

namespace App\Console\Commands;

use App\Common;
use App\DataServices\PeopleJob\PeopleJobRepo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPeopleJobCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "PeopleJob:check";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check if candidates were not selected on jobs.";

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
        $this->info('Start. Check and update job people from status = 1 to status = 0 after 24 hours');

        $repo = new PeopleJobRepo();
        $repo->updateWhere([
            ['status', 1],
            [DB::raw('DATE_ADD(created_at, INTERVAL 1 DAY)'), '<', DB::raw('NOW()')]
        ], ['status' => 0]);

        $this->info('End. Checked and updated job people from status = 1 to status = 0 after 24 hours');
        return true;
    }
}
