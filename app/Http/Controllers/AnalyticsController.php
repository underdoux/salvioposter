<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class AnalyticsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display analytics dashboard.
     */
    public function index()
    {
        $posts = auth()->user()->posts()
            ->with('analytics')
            ->latest()
            ->paginate(10);

        return view('analytics.index', compact('posts'));
    }

    /**
     * Display analytics for a specific post.
     */
    public function show(Post $post)
    {
        $this->authorize('view', $post);

        $analytics = $post->analytics;
        if (!$analytics) {
            $analytics = $post->analytics()->create([
                'views' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ]);
        }

        return view('analytics.show', compact('post', 'analytics'));
    }

    /**
     * Export analytics data as CSV.
     */
    public function export()
    {
        $posts = auth()->user()->posts()
            ->with('analytics')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics.csv"',
        ];

        $callback = function() use ($posts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Post Title', 'Views', 'Likes', 'Comments', 'Shares', 'Engagement Score']);

            foreach ($posts as $post) {
                $analytics = $post->analytics;
                fputcsv($file, [
                    $post->title,
                    $analytics->views ?? 0,
                    $analytics->likes ?? 0,
                    $analytics->comments ?? 0,
                    $analytics->shares ?? 0,
                    $analytics->engagement_score ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate a performance report for a post.
     */
    public function generateReport(Post $post)
    {
        $this->authorize('view', $post);

        $analytics = $post->analytics;
        $dailyViews = json_decode($analytics->daily_views ?? '[]', true);
        $referrers = json_decode($analytics->referrers ?? '[]', true);

        $report = [
            'post' => $post,
            'analytics' => $analytics,
            'dailyViews' => $dailyViews,
            'referrers' => $referrers,
            'weeklyGrowth' => $this->calculateWeeklyGrowth($dailyViews),
        ];

        return view('analytics.report', compact('report'));
    }

    /**
     * Calculate weekly growth from daily views.
     */
    private function calculateWeeklyGrowth(array $dailyViews): array
    {
        $weeks = [];
        $currentWeek = [];
        $weekTotal = 0;

        foreach ($dailyViews as $date => $views) {
            $weekday = date('w', strtotime($date));
            $weekTotal += $views;

            if ($weekday == 6) { // Saturday
                $weeks[] = $weekTotal;
                $weekTotal = 0;
            }
        }

        if ($weekTotal > 0) {
            $weeks[] = $weekTotal;
        }

        return $weeks;
    }
}
