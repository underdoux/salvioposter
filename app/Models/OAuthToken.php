<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token will expire soon (within the next hour).
     */
    public function willExpireSoon(): bool
    {
        return $this->expires_at->diffInMinutes(now()) < 60;
    }

    /**
     * Get the number of seconds until the token expires.
     */
    public function getExpiresInAttribute(): int
    {
        return max(0, Carbon::now()->diffInSeconds($this->expires_at));
    }

    /**
     * Check if the token needs to be refreshed.
     */
    public function needsRefresh(): bool
    {
        return $this->hasExpired() || $this->willExpireSoon();
    }
}
