@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
    </div>

    <div class="p-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Draft Posts</h3>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ auth()->user()->draftPosts()->count() }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Published Posts</h3>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ auth()->user()->publishedPosts()->count() }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Failed Posts</h3>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ auth()->user()->failedPosts()->count() }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4 mb-6">
            <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Generate New Post
            </a>
            <a href="#" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                View All Posts
            </a>
        </div>

        <!-- Recent Posts -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Posts</h3>
            
            @if(auth()->user()->posts()->count() > 0)
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul role="list" class="divide-y divide-gray-200">
                        @foreach(auth()->user()->posts()->latest()->take(5)->get() as $post)
                            <li>
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="truncate">
                                            <div class="flex text-sm">
                                                <p class="font-medium text-indigo-600 truncate">{{ $post->title }}</p>
                                            </div>
                                            <div class="mt-2 flex">
                                                <div class="flex items-center text-sm text-gray-500">
                                                    <span>{{ $post->created_at->format('M d, Y') }}</span>
                                                    <span class="mx-2">•</span>
                                                    <span class="capitalize">{{ $post->status }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-2 flex-shrink-0 flex">
                                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">No posts yet. Start by generating your first post!</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
