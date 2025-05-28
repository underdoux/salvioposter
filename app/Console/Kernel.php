<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\PublishScheduledPosts::class,
        Commands\SyncPostAnalytics::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check for scheduled posts every minute
        $schedule->command('posts:publish-scheduled')
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Sync analytics data every hour
        $schedule->command('posts:sync-analytics')
                ->hourly()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/analytics.log'));
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
