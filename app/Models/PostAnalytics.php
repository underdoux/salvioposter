<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostAnalytics extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'post_id',
        'views',
        'comments',
        'likes',
        'referrers',
        'daily_views',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'referrers' => 'array',
        'daily_views' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the post that owns the analytics.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the total engagement score.
     */
    public function getEngagementScoreAttribute(): float
    {
        // Simple engagement score calculation
        // Can be adjusted based on importance of each metric
        return ($this->views * 1) + 
               ($this->comments * 5) + 
               ($this->likes * 3);
    }

    /**
     * Get the view trend (percentage change over last sync).
     */
    public function getViewTrendAttribute(): float
    {
        if (empty($this->daily_views)) {
            return 0;
        }

        $views = array_values($this->daily_views);
        if (count($views) < 2) {
            return 0;
        }

        $current = end($views);
        $previous = prev($views);

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get the top referrers.
     */
    public function getTopReferrersAttribute(): array
    {
        if (empty($this->referrers)) {
            return [];
        }

        arsort($this->referrers);
        return array_slice($this->referrers, 0, 5, true);
    }

    /**
     * Get formatted view count.
     */
    public function getFormattedViewsAttribute(): string
    {
        return number_format($this->views);
    }

    /**
     * Get the view growth rate per day.
     */
    public function getDailyGrowthRateAttribute(): float
    {
        if (empty($this->daily_views)) {
            return 0;
        }

        $views = array_values($this->daily_views);
        $days = count($views);

        if ($days < 2) {
            return 0;
        }

        $firstDay = $views[0];
        $lastDay = end($views);

        if ($firstDay == 0) {
            return $lastDay > 0 ? 100 : 0;
        }

        return round((($lastDay - $firstDay) / $firstDay) * 100 / $days, 2);
    }
}
