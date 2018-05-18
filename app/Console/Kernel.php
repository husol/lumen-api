<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\HealthCheckCommand::class,
        Commands\CheckPeopleFbCountCommand::class,
        Commands\CheckPeopleJobCommand::class,
        Commands\CheckPeopleJobPostCommand::class,
        Commands\RemindCheckinCommand::class,
        Commands\RemindRatingCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Run health check
        $schedule->command('Health:check')->everyMinute();

        // Run application commands
        $schedule->command('People:checkFbCount')->cron('0 */4 * * *');
        $schedule->command('PeopleJob:check')->everyMinute();
        $schedule->command('PeopleJob:checkin')->everyMinute();
        $schedule->command('PeopleJobPost:check')->everyFiveMinutes();
        $schedule->command('PeopleFeedback:rating')->dailyAt('08:00');
    }
}
