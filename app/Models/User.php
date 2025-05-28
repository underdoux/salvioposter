<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        ];
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
}
