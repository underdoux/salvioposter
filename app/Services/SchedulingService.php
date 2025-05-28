<?php

namespace App\Services;

use App\Models\Post;
use App\Models\ScheduledPost;
use App\Services\BloggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SchedulingService
{
    /**
     * Schedule a post for publishing.
     */
    public function schedulePost(Post $post, string $scheduledAt): ScheduledPost
    {
        $scheduledTime = Carbon::parse($scheduledAt);

        if ($scheduledTime->isPast()) {
            throw new \InvalidArgumentException('Cannot schedule post in the past');
        }

        return ScheduledPost::create([
            'post_id' => $post->id,
            'user_id' => $post->user_id,
            'scheduled_at' => $scheduledTime,
            'status' => 'pending',
        ]);
    }

    /**
     * Reschedule a post.
     */
    public function reschedulePost(ScheduledPost $scheduledPost, string $newScheduledAt): bool
    {
        $scheduledTime = Carbon::parse($newScheduledAt);

        if ($scheduledTime->isPast()) {
            throw new \InvalidArgumentException('Cannot reschedule post to a past date');
        }

        return $scheduledPost->update([
            'scheduled_at' => $scheduledTime,
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Cancel a scheduled post.
     */
    public function cancelScheduledPost(ScheduledPost $scheduledPost): bool
    {
        return $scheduledPost->delete();
    }

    /**
     * Retry a failed scheduled post.
     */
    public function retryScheduledPost(ScheduledPost $scheduledPost): bool
    {
        if ($scheduledPost->scheduled_at->isPast()) {
            $scheduledPost->scheduled_at = now()->addMinutes(5);
        }

        return $scheduledPost->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Process scheduled posts that are due.
     *
     * @throws \Exception If there's an error processing the posts
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If a post is not found
     */
    public function processScheduledPosts(): void
    {
        $dueScheduledPosts = ScheduledPost::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('post')
            ->get();

        foreach ($dueScheduledPosts as $scheduledPost) {
            try {
                $post = $scheduledPost->post;
                if (!$post) {
                    throw new ModelNotFoundException("Post not found for scheduled post: {$scheduledPost->id}");
                }

                if (!$post->user) {
                    throw new ModelNotFoundException("User not found for post: {$post->id}");
                }
                
                // Try to publish to Blogger
                $bloggerService = new BloggerService($post->user);

                if (!$bloggerService->getClient()->getAccessToken()) {
                    throw new \Exception("No valid OAuth token found for user: {$post->user->id}");
                }
                
                if ($post->blogger_post_id) {
                    $bloggerService->updatePost($post);
                } else {
                    $bloggerService->createPost($post);
                }

                // Update post status
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                // Create success notification
                $post->user->notifications()->create([
                    'type' => 'scheduled_post_published',
                    'title' => 'Scheduled Post Published',
                    'message' => "Your scheduled post '{$post->title}' has been published successfully.",
                    'data' => [
                        'post_id' => $post->id,
                        'scheduled_at' => $scheduledPost->scheduled_at
                    ],
                    'read' => false
                ]);

                // Delete the scheduled post after successful publishing
                $scheduledPost->delete();

                Log::info("Successfully published scheduled post: {$post->id}");
            } catch (\Exception $e) {
                Log::error("Failed to publish scheduled post: {$scheduledPost->id}", [
                    'error' => $e->getMessage(),
                    'post_id' => $post->id,
                    'user_id' => $post->user_id
                ]);

                // Update scheduled post status
                $scheduledPost->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                // Create failure notification
                $post->user->notifications()->create([
                    'type' => 'scheduled_post_failed',
                    'title' => 'Scheduled Post Failed',
                    'message' => "Failed to publish scheduled post '{$post->title}'. Error: " . $e->getMessage(),
                    'data' => [
                        'post_id' => $post->id,
                        'scheduled_at' => $scheduledPost->scheduled_at,
                        'error' => $e->getMessage()
                    ],
                    'read' => false
                ]);
            }
        }
    }
}
