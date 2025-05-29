<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Notification;
use App\Services\BloggerService;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    protected $schedulingService;
    protected $bloggerService;

    public function __construct(
        SchedulingService $schedulingService,
        BloggerService $bloggerService
    ) {
        $this->middleware('auth');
        $this->schedulingService = $schedulingService;
        $this->bloggerService = $bloggerService;
    }

    /**
     * Display a listing of posts.
     */
    public function index()
    {
        $this->authorize('viewAny', Post::class);
        $posts = auth()->user()->posts()->latest()->paginate(10);
        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $this->authorize('create', Post::class);
        return view('posts.create');
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request)
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

        if ($request->has('schedule') && $request->filled('scheduled_at')) {
            try {
                $this->schedulingService->schedulePost($post, $request->scheduled_at);
                return redirect()->route('posts.edit', $post)
                    ->with('success', 'Post created and scheduled successfully!');
            } catch (\Exception $e) {
                Log::error('Failed to schedule post: ' . $e->getMessage());
                return redirect()->route('posts.edit', $post)
                    ->with('warning', 'Post created but scheduling failed.')
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

        Notification::createPostUpdated($post->user, $post);

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
                $this->bloggerService->deletePost($post);
            }
            
            $title = $post->title;
            $user = $post->user;
            
            $post->delete();
            
            Notification::createPostDeleted($user, $title);

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
            if ($post->blogger_post_id) {
                $this->bloggerService->updatePost($post);
                $message = 'Post updated on Blogger successfully!';
            } else {
                $this->bloggerService->createPost($post);
                $message = 'Post published to Blogger successfully!';
            }

            Notification::createPostPublished($post->user, $post);

            return redirect()->route('posts.show', $post)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to publish post: ' . $e->getMessage());
            return back()->with('error', 'Failed to publish post. ' . $e->getMessage());
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
