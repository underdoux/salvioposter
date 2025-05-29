<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
        ];
    }

    /**
     * Get the user's notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all posts for the user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the user's OAuth token.
     */
    public function oauthToken()
    {
        return $this->hasOne(OAuthToken::class);
    }

    /**
     * Check if user has valid OAuth token.
     *
     * @return bool
     */
    public function hasValidOAuthToken()
    {
        return $this->oauthToken && !$this->oauthToken->hasExpired();
    }

    /**
     * Get draft posts for the user.
     */
    public function draftPosts()
    {
        return $this->posts()->draft();
    }

    /**
     * Get published posts for the user.
     */
    public function publishedPosts()
    {
        return $this->posts()->published();
    }

    /**
     * Get failed posts for the user.
     */
    public function failedPosts()
    {
        return $this->posts()->failed();
    }

    /**
     * Get all scheduled posts for the user.
     */
    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }

    /**
     * Get all posts that are scheduled through the scheduling system.
     */
    public function scheduledPostsThrough(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, ScheduledPost::class, 'user_id', 'id', 'id', 'post_id');
    }
}
