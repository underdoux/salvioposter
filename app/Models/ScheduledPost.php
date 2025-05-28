<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ScheduledPost extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'scheduled_at',
        'status',
        'failure_reason',
        'retry_count',
        'last_attempt_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    /**
     * Get the post that is scheduled.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who scheduled the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending scheduled posts.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed scheduled posts.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include completed scheduled posts.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include posts that are due for publishing.
     */
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '<=', Carbon::now())
                    ->where('retry_count', '<', 3);
    }

    /**
     * Check if the scheduled post is due for publishing.
     */
    public function isDue(): bool
    {
        return $this->status === 'pending' &&
               $this->scheduled_at->isPast() &&
               $this->retry_count < 3;
    }

    /**
     * Mark the scheduled post as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'last_attempt_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark the scheduled post as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'retry_count' => $this->retry_count + 1,
            'last_attempt_at' => Carbon::now(),
        ]);
    }

    /**
     * Reset the failed status to pending.
     */
    public function retry(): void
    {
        if ($this->retry_count < 3) {
            $this->update([
                'status' => 'pending',
                'failure_reason' => null,
            ]);
        }
    }

    /**
     * Get the formatted scheduled date.
     */
    public function getFormattedScheduledDateAttribute(): string
    {
        return $this->scheduled_at->format('M d, Y h:i A');
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
