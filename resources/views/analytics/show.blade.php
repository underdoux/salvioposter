@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Post Header -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $post->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Published {{ $post->published_at?->diffForHumans() }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <form action="{{ route('analytics.sync', $post) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Sync Analytics
                        </button>
                    </form>
                    <a href="{{ $post->blogger_post_url }}" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        View on Blogger
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
        <!-- Views -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Total Views</dt>
                <dd class="mt-1 text-3xl font-semibold text-indigo-600">
                    {{ number_format($post->analytics?->views ?? 0) }}
                </dd>
                @if($post->analytics?->view_trend > 0)
                    <p class="mt-2 text-sm text-green-600">
                        ↑ {{ abs($post->analytics->view_trend) }}% increase
                    </p>
                @elseif($post->analytics?->view_trend < 0)
                    <p class="mt-2 text-sm text-red-600">
                        ↓ {{ abs($post->analytics->view_trend) }}% decrease
                    </p>
                @endif
            </div>
        </div>

        <!-- Comments -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Comments</dt>
                <dd class="mt-1 text-3xl font-semibold text-green-600">
                    {{ number_format($post->analytics?->comments ?? 0) }}
                </dd>
                <p class="mt-2 text-sm text-gray-500">
                    Engagement Rate: {{ number_format(($post->analytics?->comments ?? 0) / max(($post->analytics?->views ?? 1), 1) * 100, 1) }}%
                </p>
            </div>
        </div>

        <!-- Likes -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <dt class="text-sm font-medium text-gray-500">Likes</dt>
                <dd class="mt-1 text-3xl font-semibold text-pink-600">
                    {{ number_format($post->analytics?->likes ?? 0) }}
                </dd>
                <p class="mt-2 text-sm text-gray-500">
                    Like Rate: {{ number_format(($post->analytics?->likes ?? 0) / max(($post->analytics?->views ?? 1), 1) * 100, 1) }}%
                </p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Daily Views Chart -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Views</h3>
                <canvas id="dailyViewsChart" class="w-full" height="300"></canvas>
            </div>
        </div>

        <!-- Referrers Chart -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top Referrers</h3>
                @if($post->analytics?->referrers)
                    <canvas id="referrersChart" class="w-full" height="300"></canvas>
                @else
                    <p class="text-gray-500 text-sm">No referrer data available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Analytics Details -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Analytics Details</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Daily Growth Rate</dt>
                    <dd class="mt-1 text-lg text-gray-900">
                        {{ number_format($post->analytics?->daily_growth_rate ?? 0, 1) }}%
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Engagement Score</dt>
                    <dd class="mt-1 text-lg text-gray-900">
                        {{ number_format($post->analytics?->engagement_score ?? 0) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Synced</dt>
                    <dd class="mt-1 text-lg text-gray-900">
                        {{ $post->analytics?->last_synced_at?->diffForHumans() ?? 'Never' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Average Daily Views</dt>
                    <dd class="mt-1 text-lg text-gray-900">
                        @if($post->analytics?->daily_views)
                            {{ number_format(array_sum($post->analytics->daily_views) / count($post->analytics->daily_views)) }}
                        @else
                            0
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Views Chart
    const dailyViewsCtx = document.getElementById('dailyViewsChart').getContext('2d');
    new Chart(dailyViewsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($post->analytics?->daily_views ?? [])) !!},
            datasets: [{
                label: 'Views',
                data: {!! json_encode(array_values($post->analytics?->daily_views ?? [])) !!},
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

    @if($post->analytics?->referrers)
    // Referrers Chart
    const referrersCtx = document.getElementById('referrersChart').getContext('2d');
    new Chart(referrersCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($post->analytics->top_referrers)) !!},
            datasets: [{
                data: {!! json_encode(array_values($post->analytics->top_referrers)) !!},
                backgroundColor: [
                    'rgba(79, 70, 229, 0.2)',
                    'rgba(16, 185, 129, 0.2)',
                    'rgba(245, 158, 11, 0.2)',
                    'rgba(239, 68, 68, 0.2)',
                    'rgba(167, 139, 250, 0.2)'
                ],
                borderColor: [
                    'rgb(79, 70, 229)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                    'rgb(167, 139, 250)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    @endif
});
</script>
@endpush
@endsection
