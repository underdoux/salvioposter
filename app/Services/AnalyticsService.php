<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAnalytics;
use Carbon\Carbon;
use Google_Service_Blogger;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    protected $bloggerService;
    protected $bloggerClient;

    public function __construct(BloggerService $bloggerService)
    {
        $this->bloggerService = $bloggerService;
        $this->bloggerClient = $bloggerService->getClient();
    }

    /**
     * Sync analytics for a specific post.
     */
    public function syncPostAnalytics(Post $post): void
    {
        if (!$post->blogger_post_id) {
            return;
        }

        try {
            $bloggerService = new Google_Service_Blogger($this->bloggerClient);
            $blogPost = $bloggerService->posts->get(
                config('services.google.blog_id'),
                $post->blogger_post_id
            );

            $analytics = $post->analytics ?? new PostAnalytics(['post_id' => $post->id]);
            $currentViews = $analytics->views ?? 0;
            $currentComments = $analytics->comments ?? 0;
            $currentLikes = $analytics->likes ?? 0;

            // Update analytics data
            $analytics->views = $blogPost->getBlog()->getPages()->getTotalItems();
            $analytics->comments = $blogPost->getReplies()->getTotalItems();
            $analytics->likes = $blogPost->getLikes() ? $blogPost->getLikes()->getTotalItems() : 0;

            // Update daily views
            $dailyViews = $analytics->daily_views ?? [];
            $today = Carbon::now()->format('Y-m-d');
            $dailyViews[$today] = $analytics->views;

            // Keep only last 30 days
            $dailyViews = array_slice($dailyViews, -30, null, true);
            $analytics->daily_views = $dailyViews;

            // Update referrers if available
            if ($blogPost->getBlog()->getPages()->getReferrers()) {
                $analytics->referrers = collect($blogPost->getBlog()->getPages()->getReferrers())
                    ->mapWithKeys(function ($referrer) {
                        return [$referrer->getId() => $referrer->getCount()];
                    })
                    ->toArray();
            }

            $analytics->last_synced_at = now();
            $post->analytics()->save($analytics);

            // Log significant changes
            if ($analytics->views - $currentViews > 100) {
                Log::info("Post {$post->id} had significant view increase: +" . ($analytics->views - $currentViews));
            }

            if ($analytics->comments - $currentComments > 10) {
                Log::info("Post {$post->id} had significant comment increase: +" . ($analytics->comments - $currentComments));
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync analytics for post {$post->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync analytics for all published posts.
     */
    public function syncAllPostsAnalytics(): void
    {
        $posts = Post::where('status', 'posted')
            ->whereNotNull('blogger_post_id')
            ->get();

        foreach ($posts as $post) {
            try {
                $this->syncPostAnalytics($post);
            } catch (\Exception $e) {
                // Log error but continue with other posts
                Log::error("Failed to sync analytics for post {$post->id}: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Get analytics summary for a user's posts.
     */
    public function getUserAnalyticsSummary($userId): array
    {
        $posts = Post::where('user_id', $userId)
            ->where('status', 'posted')
            ->with('analytics')
            ->get();

        $totalViews = 0;
        $totalComments = 0;
        $totalLikes = 0;
        $topPosts = [];
        $viewTrends = [];

        foreach ($posts as $post) {
            if ($post->analytics) {
                $totalViews += $post->analytics->views;
                $totalComments += $post->analytics->comments;
                $totalLikes += $post->analytics->likes;

                $topPosts[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'views' => $post->analytics->views,
                    'engagement' => $post->analytics->engagement_score,
                ];

                $viewTrends[] = [
                    'date' => $post->analytics->last_synced_at->format('Y-m-d'),
                    'views' => $post->analytics->views,
                ];
            }
        }

        // Sort top posts by engagement score
        usort($topPosts, fn($a, $b) => $b['engagement'] - $a['engagement']);
        $topPosts = array_slice($topPosts, 0, 5);

        return [
            'total_views' => $totalViews,
            'total_comments' => $totalComments,
            'total_likes' => $totalLikes,
            'top_posts' => $topPosts,
            'view_trends' => $viewTrends,
            'post_count' => $posts->count(),
        ];
    }
}
