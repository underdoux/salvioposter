<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\BloggerService;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    protected $bloggerService;
    protected $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->middleware('oauth.valid');
    }

    /**
     * Display a listing of posts.
     */
    public function index()
    {
        $posts = auth()->user()->posts()->latest()->paginate(10);
        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = auth()->user()->posts()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => 'draft',
        ]);

        // Handle scheduling if requested
        if ($request->has('schedule') && $request->filled('scheduled_at')) {
            try {
                $this->schedulingService->schedulePost($post, $request->scheduled_at);
                return redirect()->route('posts.edit', $post)
                    ->with('success', 'Post created and scheduled successfully!');
            } catch (\Exception $e) {
                Log::error('Failed to schedule post: ' . $e->getMessage());
                return redirect()->route('posts.edit', $post)
                    ->with('warning', 'Post created but scheduling failed. You can try scheduling again.')
                    ->with('error', $e->getMessage());
            }
        }

        return redirect()->route('posts.edit', $post)
            ->with('success', 'Post created successfully!');
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post)
    {
        $this->authorize('view', $post);
        return view('posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    /**
     * Update the specified post in storage.
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        // Handle scheduling
        if ($request->has('schedule') && $request->filled('scheduled_at')) {
            try {
                if ($post->scheduledPost) {
                    $this->schedulingService->reschedulePost($post->scheduledPost, $request->scheduled_at);
                    $message = 'Post updated and rescheduled successfully!';
                } else {
                    $this->schedulingService->schedulePost($post, $request->scheduled_at);
                    $message = 'Post updated and scheduled successfully!';
                }
            } catch (\Exception $e) {
                Log::error('Failed to schedule post: ' . $e->getMessage());
                return redirect()->route('posts.edit', $post)
                    ->with('warning', 'Post updated but scheduling failed.')
                    ->with('error', $e->getMessage());
            }
        }

        return redirect()->route('posts.edit', $post)
            ->with('success', $message ?? 'Post updated successfully!');
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        try {
            if ($post->blogger_post_id) {
                $bloggerService = new BloggerService(auth()->user());
                $bloggerService->deletePost($post);
            } else {
                $post->delete();
            }

            return redirect()->route('posts.index')
                ->with('success', 'Post deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete post: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete post. Please try again.');
        }
    }

    /**
     * Publish the post to Blogger.
     */
    public function publish(Post $post)
    {
        $this->authorize('update', $post);

        try {
            $bloggerService = new BloggerService(auth()->user());
            
            if ($post->blogger_post_id) {
                $bloggerService->updatePost($post);
                $message = 'Post updated on Blogger successfully!';
            } else {
                $bloggerService->createPost($post);
                $message = 'Post published to Blogger successfully!';
            }

            return redirect()->route('posts.show', $post)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to publish post: ' . $e->getMessage());
            return back()->with('error', 'Failed to publish post. Please try again.');
        }
    }

    /**
     * Preview the post before publishing.
     */
    public function preview(Post $post)
    {
        $this->authorize('view', $post);
        return view('posts.preview', compact('post'));
    }
}
