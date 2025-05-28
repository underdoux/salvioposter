<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Services\BloggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all scheduled posts that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to process scheduled posts...');
        $count = 0;

        $duePosts = ScheduledPost::due()->with(['post', 'user'])->get();

        foreach ($duePosts as $scheduledPost) {
            try {
                $this->info("Processing scheduled post ID: {$scheduledPost->id} for post: {$scheduledPost->post->title}");
                
                $bloggerService = new BloggerService($scheduledPost->user);
                $bloggerService->publishScheduledPost($scheduledPost->post);
                
                $count++;
                $this->info("Successfully published post ID: {$scheduledPost->post->id}");
            } catch (\Exception $e) {
                $this->error("Failed to publish post ID {$scheduledPost->post->id}: " . $e->getMessage());
                Log::error("Failed to publish scheduled post ID {$scheduledPost->id}: " . $e->getMessage());
                continue;
            }
        }

        $message = $count > 0 
            ? "Successfully published {$count} scheduled post(s)." 
            : "No scheduled posts were due for publication.";
        
        $this->info($message);
        return 0;
    }
}
