<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPostAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:sync-analytics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync analytics data for all published posts';

    /**
     * The analytics service instance.
     */
    protected $analyticsService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting analytics sync...');
        $startTime = now();

        try {
            $this->analyticsService->syncAllPostsAnalytics();
            
            $duration = now()->diffInSeconds($startTime);
            $this->info("Analytics sync completed successfully in {$duration} seconds.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to sync analytics: ' . $e->getMessage());
            Log::error('Analytics sync failed: ' . $e->getMessage());
            
            return 1;
        }
    }
}
