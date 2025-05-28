<?php

namespace App\Console\Commands;

use App\Services\SchedulingService;
use Illuminate\Console\Command;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled posts that are due for publishing';

    /**
     * The scheduling service instance.
     */
    protected $schedulingService;

    /**
     * Create a new command instance.
     */
    public function __construct(SchedulingService $schedulingService)
    {
        parent::__construct();
        $this->schedulingService = $schedulingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing scheduled posts...');

        try {
            $this->schedulingService->processScheduledPosts();
            $this->info('Successfully processed scheduled posts.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled posts: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
