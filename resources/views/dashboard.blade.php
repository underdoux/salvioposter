@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Posts Overview -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Posts Overview</h3>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Posts</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ auth()->user()->posts()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Published</dt>
                        <dd class="mt-1 text-3xl font-semibold text-green-600">{{ auth()->user()->publishedPosts()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Drafts</dt>
                        <dd class="mt-1 text-3xl font-semibold text-yellow-600">{{ auth()->user()->draftPosts()->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Scheduled Posts -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Scheduled Posts</h3>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Pending Publication</dt>
                        <dd class="mt-1 text-3xl font-semibold text-indigo-600">{{ auth()->user()->scheduledPosts()->pending()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Successfully Published</dt>
                        <dd class="mt-1 text-3xl font-semibold text-green-600">{{ auth()->user()->scheduledPosts()->completed()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Failed</dt>
                        <dd class="mt-1 text-3xl font-semibold text-red-600">{{ auth()->user()->scheduledPosts()->failed()->count() }}</dd>
                    </div>
                </dl>
                <div class="mt-6">
                    <a href="{{ route('scheduled.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                        View all scheduled posts →
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                <div class="mt-4 space-y-3">
                    <a href="{{ route('posts.create') }}" 
                       class="block w-full text-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Create New Post
                    </a>
                    <a href="{{ route('posts.index') }}" 
                       class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Manage Posts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-6">
        <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-200">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-4">
                    @forelse(auth()->user()->scheduledPosts()->with('post')->latest('scheduled_at')->take(5)->get() as $scheduled)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $scheduled->post->title }}</p>
                                <p class="text-sm text-gray-500">
                                    Scheduled for {{ $scheduled->formatted_scheduled_date }}
                                </p>
                            </div>
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $scheduled->status_badge_class }}">
                                {{ ucfirst($scheduled->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No recent scheduled posts.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
