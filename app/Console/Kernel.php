<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled posts every minute
        $schedule->command('posts:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old notifications weekly
        $schedule->command('notifications:cleanup')
            ->weekly()
            ->sundays()
            ->at('00:00')
            ->withoutOverlapping();

        // Sync analytics data hourly
        $schedule->command('analytics:sync')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
