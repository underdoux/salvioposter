<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\ScheduledPost;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScheduledPostController extends Controller
{
    protected $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->middleware('oauth.valid');
        $this->schedulingService = $schedulingService;
    }

    /**
     * Display a listing of scheduled posts.
     */
    public function index()
    {
        $scheduledPosts = auth()->user()->scheduledPosts()
            ->with('post')
            ->latest('scheduled_at')
            ->paginate(10);

        return view('posts.scheduled', compact('scheduledPosts'));
    }

    /**
     * Schedule a post for future publication.
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $this->schedulingService->schedulePost($post, $validated['scheduled_at']);
            return redirect()->back()->with('success', 'Post scheduled successfully.');
        } catch (\Exception $e) {
            Log::error("Scheduling error: " . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
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
            return redirect()->back()->with('success', 'Schedule updated successfully.');
        } catch (\Exception $e) {
            Log::error("Rescheduling error: " . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a scheduled post.
     */
    public function destroy(ScheduledPost $scheduledPost)
    {
        $this->authorize('delete', $scheduledPost->post);

        try {
            $this->schedulingService->cancelSchedule($scheduledPost);
            return redirect()->back()->with('success', 'Scheduled post cancelled.');
        } catch (\Exception $e) {
            Log::error("Cancellation error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to cancel scheduled post.');
        }
    }

    /**
     * Retry a failed scheduled post.
     */
    public function retry(ScheduledPost $scheduledPost)
    {
        $this->authorize('update', $scheduledPost->post);

        if ($scheduledPost->status !== 'failed') {
            return redirect()->back()->with('error', 'Only failed posts can be retried.');
        }

        try {
            $scheduledPost->retry();
            return redirect()->back()->with('success', 'Post has been queued for retry.');
        } catch (\Exception $e) {
            Log::error("Retry error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to retry scheduled post.');
        }
    }
}
