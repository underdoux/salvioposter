@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Analytics Summary -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Views -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Total Views</dt>
                <dd class="mt-1 text-3xl font-semibold text-indigo-600">{{ number_format($summary['total_views']) }}</dd>
            </div>
        </div>

        <!-- Total Comments -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Total Comments</dt>
                <dd class="mt-1 text-3xl font-semibold text-green-600">{{ number_format($summary['total_comments']) }}</dd>
            </div>
        </div>

        <!-- Total Likes -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Total Likes</dt>
                <dd class="mt-1 text-3xl font-semibold text-pink-600">{{ number_format($summary['total_likes']) }}</dd>
            </div>
        </div>

        <!-- Published Posts -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Published Posts</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($summary['post_count']) }}</dd>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Views Trend Chart -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Views Trend</h3>
                <canvas id="viewsTrendChart" class="w-full" height="300"></canvas>
            </div>
        </div>

        <!-- Top Posts Chart -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top Posts by Engagement</h3>
                <canvas id="topPostsChart" class="w-full" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Posts Analytics Table -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Posts Analytics</h3>
            <a href="{{ route('analytics.export') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Export CSV
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Likes</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($posts as $post)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ Str::limit($post->title, 50) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $post->published_at?->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($post->analytics?->views ?? 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($post->analytics?->comments ?? 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($post->analytics?->likes ?? 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('analytics.show', $post) }}" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                <form action="{{ route('analytics.sync', $post) }}" method="POST" class="inline ml-4">
                                    @csrf
                                    <button type="submit" class="text-gray-600 hover:text-gray-900">
                                        Sync
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $posts->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch analytics data for charts
    fetch('{{ route("analytics.chart-data") }}')
        .then(response => response.json())
        .then(data => {
            // Views Trend Chart
            const viewsTrendCtx = document.getElementById('viewsTrendChart').getContext('2d');
            new Chart(viewsTrendCtx, {
                type: 'line',
                data: {
                    labels: data.view_trends.map(item => item.date),
                    datasets: [{
                        label: 'Views',
                        data: data.view_trends.map(item => item.views),
                        borderColor: 'rgb(79, 70, 229)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Top Posts Chart
            const topPostsCtx = document.getElementById('topPostsChart').getContext('2d');
            new Chart(topPostsCtx, {
                type: 'bar',
                data: {
                    labels: data.top_posts.map(post => post.title),
                    datasets: [{
                        label: 'Engagement Score',
                        data: data.top_posts.map(post => post.engagement),
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: 'rgb(79, 70, 229)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading chart data:', error));
});
</script>
@endpush
@endsection
