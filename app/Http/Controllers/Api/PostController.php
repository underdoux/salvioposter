<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Notification;
use App\Services\BloggerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = auth()->user()->posts()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => 'draft',
        ]);

        Notification::createPostCreated(auth()->user(), $post);

        return response()->json(['success' => true, 'post' => $post]);
    }

    /**
     * Update the specified post in storage.
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        Notification::createPostUpdated($post->user, $post);

        return response()->json(['success' => true, 'post' => $post]);
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        
        $title = $post->title;
        $user = $post->user;

        try {
            if ($post->blogger_post_id) {
                $bloggerService = new BloggerService($post->user);
                $bloggerService->deletePost($post);
            }
            
            $post->delete();
            
            Notification::createPostDeleted($user, $title);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to delete post: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete post'], 500);
        }
    }

    /**
     * Publish the post to Blogger.
     */
    public function publish(Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        try {
            $bloggerService = new BloggerService($post->user);
            
            if ($post->blogger_post_id) {
                $bloggerService->updatePost($post);
                $message = 'Post updated on Blogger successfully!';
            } else {
                $bloggerService->createPost($post);
                $message = 'Post published to Blogger successfully!';
            }

            $post->update(['status' => 'published']);
            
            Notification::createPostPublished($post->user, $post);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('Failed to publish post: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to publish post'], 500);
        }
    }
}
