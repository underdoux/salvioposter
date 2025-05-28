<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'read' => 'boolean',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return !$this->read;
    }

    /**
     * Get the notification icon based on type.
     */
    public function getIconClassAttribute(): string
    {
        return match($this->type) {
            'analytics_update' => 'fas fa-chart-line text-blue-500',
            'post_published' => 'fas fa-check-circle text-green-500',
            'post_failed' => 'fas fa-exclamation-circle text-red-500',
            'milestone_reached' => 'fas fa-trophy text-yellow-500',
            default => 'fas fa-bell text-gray-500',
        };
    }

    /**
     * Get the notification background color based on type.
     */
    public function getBgColorClassAttribute(): string
    {
        return match($this->type) {
            'analytics_update' => 'bg-blue-50',
            'post_published' => 'bg-green-50',
            'post_failed' => 'bg-red-50',
            'milestone_reached' => 'bg-yellow-50',
            default => 'bg-gray-50',
        };
    }

    /**
     * Create an analytics update notification.
     */
    public static function createAnalyticsUpdate(User $user, Post $post, array $metrics): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => 'analytics_update',
            'title' => 'Analytics Update',
            'message' => "Your post '{$post->title}' has new analytics data.",
            'data' => [
                'post_id' => $post->id,
                'metrics' => $metrics,
            ],
        ]);
    }

    /**
     * Create a post published notification.
     */
    public static function createPostPublished(User $user, Post $post): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => 'post_published',
            'title' => 'Post Published',
            'message' => "Your post '{$post->title}' has been published successfully.",
            'data' => [
                'post_id' => $post->id,
            ],
        ]);
    }

    /**
     * Create a post failed notification.
     */
    public static function createPostFailed(User $user, Post $post, string $error): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => 'post_failed',
            'title' => 'Post Failed',
            'message' => "Failed to publish your post '{$post->title}'.",
            'data' => [
                'post_id' => $post->id,
                'error' => $error,
            ],
        ]);
    }

    /**
     * Create a milestone reached notification.
     */
    public static function createMilestoneReached(User $user, Post $post, string $milestone): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => 'milestone_reached',
            'title' => 'Milestone Reached',
            'message' => "Your post '{$post->title}' has reached {$milestone}!",
            'data' => [
                'post_id' => $post->id,
                'milestone' => $milestone,
            ],
        ]);
    }
}
