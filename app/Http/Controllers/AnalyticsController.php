<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->middleware('oauth.valid');
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display analytics dashboard.
     */
    public function index()
    {
        try {
            $summary = $this->analyticsService->getUserAnalyticsSummary(auth()->id());
            
            return view('analytics.index', [
                'summary' => $summary,
                'posts' => auth()->user()->posts()
                    ->where('status', 'posted')
                    ->with('analytics')
                    ->latest()
                    ->paginate(10)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load analytics: ' . $e->getMessage());
            return back()->with('error', 'Failed to load analytics data. Please try again.');
        }
    }

    /**
     * Display analytics for a specific post.
     */
    public function show(Post $post)
    {
        $this->authorize('view', $post);

        try {
            // Sync latest analytics data
            $this->analyticsService->syncPostAnalytics($post);
            
            return view('analytics.show', [
                'post' => $post->load('analytics')
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load analytics for post {$post->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to load post analytics. Please try again.');
        }
    }

    /**
     * Get analytics data for charts (AJAX endpoint).
     */
    public function getChartData(Request $request)
    {
        try {
            $summary = $this->analyticsService->getUserAnalyticsSummary(auth()->id());
            
            return response()->json([
                'view_trends' => $summary['view_trends'],
                'top_posts' => $summary['top_posts'],
                'total_views' => $summary['total_views'],
                'total_comments' => $summary['total_comments'],
                'total_likes' => $summary['total_likes'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load chart data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load analytics data'], 500);
        }
    }

    /**
     * Force sync analytics for a post.
     */
    public function sync(Post $post)
    {
        $this->authorize('view', $post);

        try {
            $this->analyticsService->syncPostAnalytics($post);
            return back()->with('success', 'Analytics data synced successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to sync analytics for post {$post->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to sync analytics data. Please try again.');
        }
    }

    /**
     * Export analytics data as CSV.
     */
    public function export()
    {
        try {
            $posts = auth()->user()->posts()
                ->where('status', 'posted')
                ->with('analytics')
                ->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="analytics_export_' . now()->format('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($posts) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Title', 'Published Date', 'Views', 'Comments', 'Likes', 'Engagement Score']);

                foreach ($posts as $post) {
                    if ($post->analytics) {
                        fputcsv($file, [
                            $post->title,
                            $post->published_at->format('Y-m-d H:i:s'),
                            $post->analytics->views,
                            $post->analytics->comments,
                            $post->analytics->likes,
                            $post->analytics->engagement_score,
                        ]);
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Failed to export analytics: ' . $e->getMessage());
            return back()->with('error', 'Failed to export analytics data. Please try again.');
        }
    }
}
