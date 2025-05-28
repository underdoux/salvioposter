<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\ScheduledPost;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ScheduledPostController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->middleware('auth');
        $this->schedulingService = $schedulingService;
    }

    /**
     * Display a listing of scheduled posts.
     */
    public function index()
    {
        $scheduledPosts = auth()->user()
            ->posts()
            ->whereHas('scheduledPost')
            ->with('scheduledPost')
            ->latest()
            ->paginate(10);

        return view('scheduled-posts.index', compact('scheduledPosts'));
    }

    /**
     * Schedule a post for publishing.
     */
    public function store(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $scheduledPost = $this->schedulingService->schedulePost($post, $validated['scheduled_at']);

            // Create notification
            $post->user->notifications()->create([
                'type' => 'post_scheduled',
                'title' => 'Post Scheduled',
                'message' => "Your post '{$post->title}' has been scheduled for " . $validated['scheduled_at'],
                'data' => ['post_id' => $post->id],
                'read' => false
            ]);

            return redirect()->route('posts.show', $post)
                ->with('success', 'Post scheduled successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to schedule post: ' . $e->getMessage());
            return back()->with('error', 'Failed to schedule post. Please try again.');
        }
    }

    /**
     * Update the scheduled time for a post.
     */
    public function update(Request $request, ScheduledPost $scheduledPost)
    {
        $this->authorize('update', $scheduledPost->post);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $this->schedulingService->reschedulePost($scheduledPost, $validated['scheduled_at']);

            // Create notification
            $scheduledPost->post->user->notifications()->create([
                'type' => 'post_rescheduled',
                'title' => 'Post Rescheduled',
                'message' => "Your post '{$scheduledPost->post->title}' has been rescheduled for " . $validated['scheduled_at'],
                'data' => ['post_id' => $scheduledPost->post->id],
                'read' => false
            ]);

            return redirect()->route('posts.show', $scheduledPost->post)
                ->with('success', 'Post rescheduled successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to reschedule post: ' . $e->getMessage());
            return back()->with('error', 'Failed to reschedule post. Please try again.');
        }
    }

    /**
     * Cancel a scheduled post.
     */
    public function destroy(ScheduledPost $scheduledPost)
    {
        $this->authorize('update', $scheduledPost->post);

        try {
            $this->schedulingService->cancelScheduledPost($scheduledPost);

            // Create notification
            $scheduledPost->post->user->notifications()->create([
                'type' => 'post_schedule_cancelled',
                'title' => 'Schedule Cancelled',
                'message' => "Schedule for post '{$scheduledPost->post->title}' has been cancelled.",
                'data' => ['post_id' => $scheduledPost->post->id],
                'read' => false
            ]);

            return redirect()->route('posts.show', $scheduledPost->post)
                ->with('success', 'Scheduled post cancelled successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to cancel scheduled post: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel scheduled post. Please try again.');
        }
    }

    /**
     * Display a list of failed scheduled posts.
     */
    public function failed()
    {
        $failedPosts = auth()->user()
            ->posts()
            ->whereHas('scheduledPost', function ($query) {
                $query->where('status', 'failed');
            })
            ->with('scheduledPost')
            ->latest()
            ->paginate(10);

        return view('scheduled-posts.failed', compact('failedPosts'));
    }

    /**
     * Retry a failed scheduled post.
     */
    public function retry(ScheduledPost $scheduledPost)
    {
        $this->authorize('update', $scheduledPost->post);

        try {
            $this->schedulingService->retryScheduledPost($scheduledPost);

            // Create notification
            $scheduledPost->post->user->notifications()->create([
                'type' => 'post_retry_scheduled',
                'title' => 'Post Retry Scheduled',
                'message' => "Failed post '{$scheduledPost->post->title}' has been rescheduled for retry.",
                'data' => [
                    'post_id' => $scheduledPost->post->id,
                    'scheduled_at' => $scheduledPost->scheduled_at
                ],
                'read' => false
            ]);

            return redirect()->route('posts.show', $scheduledPost->post)
                ->with('success', 'Post rescheduled for retry!');
        } catch (\Exception $e) {
            Log::error('Failed to retry scheduled post: ' . $e->getMessage());
            
            // Create failure notification
            try {
                $scheduledPost->post->user->notifications()->create([
                    'type' => 'post_retry_failed',
                    'title' => 'Post Retry Failed',
                    'message' => "Failed to retry post '{$scheduledPost->post->title}'. Error: " . $e->getMessage(),
                    'data' => ['post_id' => $scheduledPost->post->id],
                    'read' => false
                ]);
            } catch (\Exception $notificationError) {
                Log::error('Failed to create notification: ' . $notificationError->getMessage());
            }

            return back()->with('error', 'Failed to retry scheduled post: ' . $e->getMessage());
        }
    }
}
