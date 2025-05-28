@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Preview Controls -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-xl font-semibold text-gray-800">Post Preview</h2>
                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                    {{ $post->status === 'posted' ? 'bg-green-100 text-green-800' : 
                       ($post->status === 'failed' ? 'bg-red-100 text-red-800' : 
                       'bg-yellow-100 text-yellow-800') }}">
                    {{ ucfirst($post->status) }}
                </span>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('posts.edit', $post) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Edit Post
                </a>
                @if($post->status !== 'posted')
                    <form action="{{ route('posts.publish', $post) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Publish to Blogspot
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Preview Content -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <!-- Blog Header -->
        <div class="px-6 py-8 border-b border-gray-200 bg-gray-50">
            <div class="max-w-3xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $post->title }}</h1>
                <div class="flex items-center text-sm text-gray-500">
                    <span>By {{ $post->user->name }}</span>
                    <span class="mx-2">&bull;</span>
                    <span>{{ $post->created_at->format('F j, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Blog Content -->
        <div class="px-6 py-8">
            <div class="max-w-3xl mx-auto prose prose-indigo prose-lg">
                {!! Str::markdown($post->content) !!}
            </div>
        </div>

        <!-- Blog Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="max-w-3xl mx-auto flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    @if($post->published_at)
                        Published on {{ $post->published_at->format('F j, Y') }}
                    @else
                        Draft created on {{ $post->created_at->format('F j, Y') }}
                    @endif
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('posts.index') }}" 
                       class="text-sm text-gray-500 hover:text-gray-900">
                        Back to Posts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Information -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Post Information</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($post->status) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $post->created_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $post->updated_at->format('M d, Y H:i') }}</dd>
            </div>
            @if($post->published_at)
            <div>
                <dt class="text-sm font-medium text-gray-500">Published</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $post->published_at->format('M d, Y H:i') }}</dd>
            </div>
            @endif
            @if($post->blogger_post_id)
            <div>
                <dt class="text-sm font-medium text-gray-500">Blogger Post ID</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $post->blogger_post_id }}</dd>
            </div>
            @endif
        </dl>
    </div>
</div>
@endsection
