<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for significant analytics changes.
     */
    public function notifyAnalyticsUpdate(Post $post, array $metrics): void
    {
        try {
            // Check for significant changes
            $significantChanges = $this->getSignificantChanges($metrics);
            
            if (!empty($significantChanges)) {
                Notification::createAnalyticsUpdate(
                    $post->user,
                    $post,
                    $significantChanges
                );

                // Check for milestones
                $this->checkAndNotifyMilestones($post, $metrics);
            }
        } catch (\Exception $e) {
            Log::error("Failed to create analytics notification: " . $e->getMessage());
        }
    }

    /**
     * Create a notification for successful post publication.
     */
    public function notifyPostPublished(Post $post): void
    {
        try {
            Notification::createPostPublished($post->user, $post);
        } catch (\Exception $e) {
            Log::error("Failed to create post published notification: " . $e->getMessage());
        }
    }

    /**
     * Create a notification for failed post publication.
     */
    public function notifyPostFailed(Post $post, string $error): void
    {
        try {
            Notification::createPostFailed($post->user, $post, $error);
        } catch (\Exception $e) {
            Log::error("Failed to create post failed notification: " . $e->getMessage());
        }
    }

    /**
     * Get notifications for a user.
     */
    public function getUserNotifications(User $user, bool $unreadOnly = false): array
    {
        $query = $user->notifications()->latest();
        
        if ($unreadOnly) {
            $query->unread();
        }

        return [
            'notifications' => $query->take(10)->get(),
            'unread_count' => $user->notifications()->unread()->count(),
        ];
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(User $user, array $notificationIds = []): void
    {
        $query = $user->notifications();
        
        if (!empty($notificationIds)) {
            $query->whereIn('id', $notificationIds);
        }

        $query->update(['read_at' => now()]);
    }

    /**
     * Check for significant changes in metrics.
     */
    protected function getSignificantChanges(array $metrics): array
    {
        $significantChanges = [];
        $thresholds = [
            'views' => 100,      // 100 new views
            'comments' => 10,     // 10 new comments
            'likes' => 20,       // 20 new likes
        ];

        foreach ($thresholds as $metric => $threshold) {
            if (isset($metrics[$metric . '_change']) && $metrics[$metric . '_change'] >= $threshold) {
                $significantChanges[$metric] = $metrics[$metric . '_change'];
            }
        }

        return $significantChanges;
    }

    /**
     * Check and notify for reached milestones.
     */
    protected function checkAndNotifyMilestones(Post $post, array $metrics): void
    {
        $milestones = [
            'views' => [1000, 5000, 10000, 50000, 100000],
            'comments' => [100, 500, 1000, 5000],
            'likes' => [100, 500, 1000, 5000],
        ];

        foreach ($milestones as $metric => $thresholds) {
            if (!isset($metrics[$metric])) {
                continue;
            }

            $currentValue = $metrics[$metric];
            foreach ($thresholds as $threshold) {
                // Check if we just crossed this threshold
                if ($currentValue >= $threshold && 
                    (!isset($metrics['previous_' . $metric]) || $metrics['previous_' . $metric] < $threshold)) {
                    
                    Notification::createMilestoneReached(
                        $post->user,
                        $post,
                        number_format($threshold) . ' ' . str_replace('_', ' ', $metric)
                    );
                }
            }
        }
    }

    /**
     * Send email notifications for important updates.
     */
    public function sendEmailNotifications(User $user): void
    {
        try {
            $unreadNotifications = $user->notifications()
                ->unread()
                ->whereIn('type', ['post_failed', 'milestone_reached'])
                ->get();

            if ($unreadNotifications->isNotEmpty()) {
                // Here you would typically use Laravel's Mail facade to send emails
                // For now, we'll just log it
                Log::info("Would send email to {$user->email} with " . 
                         $unreadNotifications->count() . " notifications");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send email notifications: " . $e->getMessage());
        }
    }
}
