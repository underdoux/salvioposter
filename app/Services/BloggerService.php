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
    protected $blogger;
    protected $user;
    protected $blogId;

    /**
     * Create a new BloggerService instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->initializeGoogleClient();
    }

    /**
     * Initialize the Google Client with user's OAuth token.
     */
    protected function initializeGoogleClient()
    {
        try {
            $this->client = new Google_Client();
            $this->client->setClientId(config('services.google.client_id'));
            $this->client->setClientSecret(config('services.google.client_secret'));
            $this->client->setRedirectUri(config('services.google.redirect'));
            $this->client->setAccessType('offline');
            $this->client->setScopes(['https://www.googleapis.com/auth/blogger']);

            // Set the access token from the database
            if ($this->user->oauthToken) {
                $this->client->setAccessToken([
                    'access_token' => $this->user->oauthToken->access_token,
                    'refresh_token' => $this->user->oauthToken->refresh_token,
                    'token_type' => $this->user->oauthToken->token_type,
                    'expires_in' => now()->diffInSeconds($this->user->oauthToken->expires_at),
                ]);
            }

            $this->blogger = new Google_Service_Blogger($this->client);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new blog post.
     */
    public function createPost(Post $post)
    {
        try {
            $blogPost = new \Google_Service_Blogger_Post();
            $blogPost->setTitle($post->title);
            $blogPost->setContent($post->content);

            $createdPost = $this->blogger->posts->insert($this->getBlogId(), $blogPost);

            // Update local post with Blogger post ID
            $post->update([
                'blogger_post_id' => $createdPost->getId(),
                'status' => 'posted',
                'published_at' => now(),
            ]);

            return $createdPost;
        } catch (\Exception $e) {
            Log::error('Failed to create blog post: ' . $e->getMessage());
            $post->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Update an existing blog post.
     */
    public function updatePost(Post $post)
    {
        try {
            if (!$post->blogger_post_id) {
                throw new \Exception('Post has no Blogger ID');
            }

            $blogPost = new \Google_Service_Blogger_Post();
            $blogPost->setTitle($post->title);
            $blogPost->setContent($post->content);

            $updatedPost = $this->blogger->posts->update(
                $this->getBlogId(),
                $post->blogger_post_id,
                $blogPost
            );

            $post->update(['status' => 'posted']);

            return $updatedPost;
        } catch (\Exception $e) {
            Log::error('Failed to update blog post: ' . $e->getMessage());
            $post->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Delete a blog post.
     */
    public function deletePost(Post $post)
    {
        try {
            if (!$post->blogger_post_id) {
                throw new \Exception('Post has no Blogger ID');
            }

            $this->blogger->posts->delete(
                $this->getBlogId(),
                $post->blogger_post_id
            );

            $post->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete blog post: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the user's blog ID.
     */
    protected function getBlogId()
    {
        if (!$this->blogId) {
            try {
                $blogs = $this->blogger->blogs->listByUser('self');
                if ($blogs->getItems()) {
                    $this->blogId = $blogs->getItems()[0]->getId();
                } else {
                    throw new \Exception('No blogs found for this user');
                }
            } catch (\Exception $e) {
                Log::error('Failed to get blog ID: ' . $e->getMessage());
                throw $e;
            }
        }

        return $this->blogId;
    }
}
