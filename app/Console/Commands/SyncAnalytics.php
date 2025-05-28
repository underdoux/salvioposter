<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync analytics data for all posts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting analytics sync...');

        try {
            $posts = Post::with('analytics')->get();

            foreach ($posts as $post) {
                $this->syncPostAnalytics($post);
            }

            $this->info('Analytics sync completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to sync analytics: ' . $e->getMessage());
            Log::error('Analytics sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Sync analytics for a single post.
     */
    private function syncPostAnalytics(Post $post): void
    {
        $analytics = $post->analytics ?? $post->analytics()->create([
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
        ]);

        // Here you would typically fetch analytics data from Blogger API
        // For now, we'll just update the timestamps
        $analytics->touch();
    }
}
