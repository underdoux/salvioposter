<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Google_Client;
use Google_Service_Blogger;
use Illuminate\Support\Facades\Log;

class BloggerService
{
    protected $client;
    protected $service;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->initializeClient();
    }

    /**
     * Initialize the Google Client with OAuth token.
     */
    protected function initializeClient(): void
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('services.google'));
        $this->client->addScope('https://www.googleapis.com/auth/blogger');

        if ($token = $this->user->oauthToken) {
            $this->client->setAccessToken($token->access_token);

            if ($this->client->isAccessTokenExpired()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($token->refresh_token);
                $token->update([
                    'access_token' => $newToken['access_token'],
                    'expires_at' => now()->addSeconds($newToken['expires_in']),
                ]);
            }
        }

        $this->service = new Google_Service_Blogger($this->client);
    }

    /**
     * Create a new blog post.
     */
    public function createPost(Post $post): void
    {
        try {
            $blogPost = new \Google_Service_Blogger_Post();
            $blogPost->setTitle($post->title);
            $blogPost->setContent($post->content);

            $createdPost = $this->service->posts->insert(
                config('services.google.blog_id'),
                $blogPost
            );

            $post->update([
                'blogger_post_id' => $createdPost->getId(),
                'status' => 'posted',
                'published_at' => now(),
            ]);

            Log::info("Post {$post->id} published to Blogger successfully.");
        } catch (\Exception $e) {
            Log::error("Failed to publish post {$post->id} to Blogger: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing blog post.
     */
    public function updatePost(Post $post): void
    {
        try {
            $blogPost = new \Google_Service_Blogger_Post();
            $blogPost->setTitle($post->title);
            $blogPost->setContent($post->content);

            $this->service->posts->update(
                config('services.google.blog_id'),
                $post->blogger_post_id,
                $blogPost
            );

            $post->update([
                'status' => 'posted',
                'published_at' => now(),
            ]);

            Log::info("Post {$post->id} updated on Blogger successfully.");
        } catch (\Exception $e) {
            Log::error("Failed to update post {$post->id} on Blogger: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a blog post.
     */
    public function deletePost(Post $post): void
    {
        try {
            if ($post->blogger_post_id) {
                $this->service->posts->delete(
                    config('services.google.blog_id'),
                    $post->blogger_post_id
                );
            }

            $post->delete();
            Log::info("Post {$post->id} deleted from Blogger successfully.");
        } catch (\Exception $e) {
            Log::error("Failed to delete post {$post->id} from Blogger: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Publish a scheduled post.
     */
    public function publishScheduledPost(Post $post): void
    {
        try {
            if ($post->blogger_post_id) {
                $this->updatePost($post);
            } else {
                $this->createPost($post);
            }

            if ($post->scheduledPost) {
                $post->scheduledPost->markAsCompleted();
            }
        } catch (\Exception $e) {
            if ($post->scheduledPost) {
                $post->scheduledPost->markAsFailed($e->getMessage());
            }
            throw $e;
        }
    }
}
