<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the analytics for the post.
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(PostAnalytics::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
        'blogger_post_id',
        'published_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include draft posts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope a query to only include failed posts.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get the scheduled post associated with this post.
     */
    public function scheduledPost(): HasOne
    {
        return $this->hasOne(ScheduledPost::class);
    }

    /**
     * Check if the post is scheduled for future publication.
     */
    public function isScheduled(): bool
    {
        return $this->scheduledPost()->where('status', 'pending')->exists();
    }
}
