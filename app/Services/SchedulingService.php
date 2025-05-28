<?php

namespace App\Services;

use App\Models\Post;
use App\Models\ScheduledPost;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class SchedulingService
{
    protected $bloggerService;

    public function __construct(BloggerService $bloggerService)
    {
        $this->bloggerService = $bloggerService;
    }

    /**
     * Schedule a post for future publication
     */
    public function schedulePost(Post $post, string $datetime): ScheduledPost
    {
        $scheduledAt = Carbon::parse($datetime);

        if ($scheduledAt->isPast()) {
            throw new Exception("Scheduling time must be in the future.");
        }

        return ScheduledPost::create([
            'post_id' => $post->id,
            'user_id' => $post->user_id,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending'
        ]);
    }

    /**
     * Reschedule an existing scheduled post
     */
    public function reschedulePost(ScheduledPost $scheduledPost, string $newDateTime): void
    {
        $newTime = Carbon::parse($newDateTime);
        
        if ($newTime->isPast()) {
            throw new Exception("New schedule time must be in the future.");
        }

        if ($scheduledPost->status === 'completed') {
            throw new Exception("Cannot reschedule a completed post.");
        }

        $scheduledPost->update([
            'scheduled_at' => $newTime,
            'status' => 'pending',
            'failure_reason' => null,
            'retry_count' => 0
        ]);
    }

    /**
     * Cancel a scheduled post
     */
    public function cancelSchedule(ScheduledPost $scheduledPost): void
    {
        if ($scheduledPost->status === 'completed') {
            throw new Exception("Cannot cancel a completed post.");
        }

        $scheduledPost->delete();
    }

    /**
     * Process due scheduled posts
     */
    public function processDueScheduledPosts(): void
    {
        $duePosts = ScheduledPost::due()->get();

        foreach ($duePosts as $scheduledPost) {
            try {
                $post = $scheduledPost->post;
                
                if ($post->blogger_post_id) {
                    $this->bloggerService->updatePost($post);
                } else {
                    $this->bloggerService->createPost($post);
                }

                $scheduledPost->markAsCompleted();
                Log::info("Successfully published scheduled post ID: {$post->id}");
            } catch (Exception $e) {
                Log::error("Failed to publish scheduled post ID {$post->id}: " . $e->getMessage());
                $scheduledPost->markAsFailed($e->getMessage());
            }
        }
    }
}
