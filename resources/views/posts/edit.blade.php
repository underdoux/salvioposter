@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Edit Post</h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Status:</span>
                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                    {{ $post->status === 'posted' ? 'bg-green-100 text-green-800' : 
                       ($post->status === 'failed' ? 'bg-red-100 text-red-800' : 
                       'bg-yellow-100 text-yellow-800') }}">
                    {{ ucfirst($post->status) }}
                </span>
            </div>
        </div>

        <form action="{{ route('posts.update', $post) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Title
                    </label>
                    <div class="mt-1">
                        <input type="text" name="title" id="title" 
                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('title') border-red-300 @enderror"
                               value="{{ old('title', $post->title) }}" required>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Content
                    </label>
                    <div class="mt-1">
                        <textarea name="content" id="content" rows="15"
                                  class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('content') border-red-300 @enderror"
                                  required>{{ old('content', $post->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        You can use Markdown formatting for rich text.
                    </p>
                </div>

                <!-- Scheduling Options -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Scheduling</h3>
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        @if($post->scheduledPost && $post->scheduledPost->status !== 'completed')
                            <div class="mb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Currently Scheduled</p>
                                        <p class="text-sm text-gray-500">
                                            Will be published on {{ $post->scheduledPost->scheduled_at->format('M d, Y h:i A') }}
                                        </p>
                                        @if($post->scheduledPost->status === 'failed')
                                            <p class="mt-1 text-sm text-red-600">
                                                Last attempt failed: {{ $post->scheduledPost->failure_reason }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <form action="{{ route('scheduled.destroy', $post->scheduledPost) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                Cancel Schedule
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="schedule_checkbox" 
                                       name="schedule" 
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="schedule_checkbox" class="ml-2 block text-sm text-gray-900">
                                    Schedule for Future Publication
                                </label>
                            </div>
                            
                            <div id="schedule_input" class="mt-4 hidden">
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700">
                                    Publication Date and Time
                                </label>
                                <input type="datetime-local" 
                                       name="scheduled_at" 
                                       id="scheduled_at"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       min="{{ now()->format('Y-m-d\TH:i') }}">
                                <p class="mt-1 text-sm text-gray-500">
                                    Select when you want this post to be automatically published.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Preview -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                    <div class="bg-gray-50 p-4 rounded-md prose max-w-none" id="preview">
                        <h1 class="text-2xl font-bold">{{ $post->title }}</h1>
                        <div class="mt-4">
                            {!! Str::markdown($post->content) !!}
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4 border-t border-gray-200">
                    <div>
                        <a href="{{ route('posts.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Back to Posts
                        </a>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" name="action" value="save"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Save Changes
                        </button>
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
        </form>
    </div>

    <!-- Post Information -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Post Information</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

@push('scripts')
<script>
    // Scheduling checkbox handler
    const scheduleCheckbox = document.getElementById('schedule_checkbox');
    const scheduleInput = document.getElementById('schedule_input');
    
    if (scheduleCheckbox) {
        scheduleCheckbox.addEventListener('change', function() {
            scheduleInput.classList.toggle('hidden', !this.checked);
            if (this.checked) {
                document.getElementById('scheduled_at').focus();
            }
        });
    }

    // Live preview functionality
    const contentInput = document.getElementById('content');
    const titleInput = document.getElementById('title');
    const preview = document.getElementById('preview');

    function updatePreview() {
        // This is a simple preview. In production, you might want to use a proper Markdown parser
        preview.innerHTML = `
            <h1 class="text-2xl font-bold">${titleInput.value}</h1>
            <div class="mt-4">${contentInput.value}</div>
        `;
    }

    contentInput.addEventListener('input', updatePreview);
    titleInput.addEventListener('input', updatePreview);
</script>
@endpush
@endsection
