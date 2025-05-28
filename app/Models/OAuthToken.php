<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at'
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the access token has expired.
     *
     * @return bool
     */
    public function hasExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token will expire soon (within 5 minutes).
     *
     * @return bool
     */
    public function willExpireSoon()
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }

    /**
     * Get the full access token with token type.
     *
     * @return string
     */
    public function getFullAccessToken()
    {
        return "{$this->token_type} {$this->access_token}";
    }
}
