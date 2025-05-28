<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Google_Client;
use Google_Service_Blogger;
use Google_Service_Blogger_Post;
use Illuminate\Support\Facades\Log;

class BloggerService
{
    protected $user;
    protected $client;
    protected $service;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('services.google'));
        $this->client->addScope('https://www.googleapis.com/auth/blogger');
        $this->service = new Google_Service_Blogger($this->client);
    }

    /**
     * Initialize OAuth token for the client.
     *
     * @throws \Exception If token is invalid or expired
     */
    private function initializeToken(): void
    {
        $token = $this->user->oauthToken;
        if (!$token || !$token->access_token) {
            throw new \Exception('User does not have a valid OAuth token');
        }

        $this->client->setAccessToken($token->access_token);

        if ($this->client->isAccessTokenExpired() && $token->refresh_token) {
            try {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($token->refresh_token);
                $token->update([
                    'access_token' => $newToken['access_token'],
                    'expires_at' => now()->addSeconds($newToken['expires_in']),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to refresh OAuth token', [
                    'user_id' => $this->user->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to refresh OAuth token: ' . $e->getMessage());
            }
        }
    }

    /**
     * Create a new post on Blogger.
     *
     * @param Post $post
     * @throws \Exception
     */
    public function createPost(Post $post): void
    {
        $this->initializeToken();

        $blogPost = new Google_Service_Blogger_Post();
        $blogPost->setTitle($post->title);
        $blogPost->setContent($post->content);

        try {
            $response = $this->service->posts->insert(
                config('services.google.blogger_blog_id'),
                $blogPost
            );

            $post->update([
                'blogger_post_id' => $response->getId(),
                'status' => 'published'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create post on Blogger', [
                'post_id' => $post->id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to create post on Blogger: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing post on Blogger.
     *
     * @param Post $post
     * @throws \Exception
     */
    public function updatePost(Post $post): void
    {
        $this->initializeToken();

        if (!$post->blogger_post_id) {
            throw new \Exception('Post does not have a Blogger ID');
        }

        $blogPost = new Google_Service_Blogger_Post();
        $blogPost->setTitle($post->title);
        $blogPost->setContent($post->content);

        try {
            $this->service->posts->update(
                config('services.google.blogger_blog_id'),
                $post->blogger_post_id,
                $blogPost
            );
        } catch (\Exception $e) {
            Log::error('Failed to update post on Blogger', [
                'post_id' => $post->id,
                'blogger_post_id' => $post->blogger_post_id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to update post on Blogger: ' . $e->getMessage());
        }
    }

    /**
     * Delete a post from Blogger.
     *
     * @param Post $post
     * @throws \Exception
     */
    public function deletePost(Post $post): void
    {
        $this->initializeToken();

        if (!$post->blogger_post_id) {
            return;
        }

        try {
            $this->service->posts->delete(
                config('services.google.blogger_blog_id'),
                $post->blogger_post_id
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete post from Blogger', [
                'post_id' => $post->id,
                'blogger_post_id' => $post->blogger_post_id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to delete post from Blogger: ' . $e->getMessage());
        }
    }

    /**
     * Get the Google Client instance.
     *
     * @return Google_Client
     */
    public function getClient(): Google_Client
    {
        return $this->client;
    }
}
