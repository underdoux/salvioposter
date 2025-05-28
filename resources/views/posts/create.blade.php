@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Create New Post</h2>
        </div>

        <form action="{{ route('posts.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-6">
                <!-- AI Content Generation -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">AI Content Generation</h3>
                    
                    <!-- Topic Input -->
                    <div class="mb-4">
                        <label for="topic" class="block text-sm font-medium text-gray-700">Topic</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="topic" id="topic" 
                                   class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                   placeholder="Enter a topic for your blog post">
                        </div>
                    </div>

                    <!-- Keywords Input -->
                    <div class="mb-4">
                        <label for="keywords" class="block text-sm font-medium text-gray-700">Keywords (comma-separated)</label>
                        <div class="mt-1">
                            <input type="text" name="keywords" id="keywords" 
                                   class="block w-full px-3 py-2 rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                   placeholder="Enter keywords separated by commas">
                        </div>
                    </div>

                    <!-- Generation Buttons -->
                    <div class="flex gap-3">
                        <button type="button" id="generateTitles"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Generate Titles
                        </button>
                        <button type="button" id="generateContent"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Generate Content
                        </button>
                        <button type="button" id="generateFullPost"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Generate Full Post
                        </button>
                    </div>

                    <!-- Generated Titles -->
                    <div id="generatedTitles" class="mt-4 hidden">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Generated Titles</h4>
                        <div class="space-y-2" id="titlesList"></div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Title
                    </label>
                    <div class="mt-1">
                        <input type="text" name="title" id="title" 
                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('title') border-red-300 @enderror"
                               value="{{ old('title') }}" required>
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
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        You can use Markdown formatting for rich text.
                    </p>
                </div>

                <!-- Scheduling Options -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
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
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('posts.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" name="action" value="draft"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="publish"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        Save and Publish
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scheduling checkbox handler
    const scheduleCheckbox = document.getElementById('schedule_checkbox');
    const scheduleInput = document.getElementById('schedule_input');
    
    scheduleCheckbox.addEventListener('change', function() {
        scheduleInput.classList.toggle('hidden', !this.checked);
        if (this.checked) {
            document.getElementById('scheduled_at').focus();
        }
    });

    const generateTitlesBtn = document.getElementById('generateTitles');
    const generateContentBtn = document.getElementById('generateContent');
    const generateFullPostBtn = document.getElementById('generateFullPost');
    const titlesList = document.getElementById('titlesList');
    const generatedTitles = document.getElementById('generatedTitles');

    // Generate Titles
    generateTitlesBtn.addEventListener('click', async function() {
        const topic = document.getElementById('topic').value;
        if (!topic) {
            alert('Please enter a topic first');
            return;
        }

        try {
            const response = await fetch('{{ route("content.titles") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ topic, count: 3 })
            });

            const data = await response.json();
            if (data.titles) {
                titlesList.innerHTML = data.titles.map(title => `
                    <div class="flex items-center space-x-2">
                        <button type="button" class="use-title text-indigo-600 hover:text-indigo-900"
                                data-title="${title}">Use</button>
                        <span>${title}</span>
                    </div>
                `).join('');
                generatedTitles.classList.remove('hidden');

                // Add click handlers for "Use" buttons
                document.querySelectorAll('.use-title').forEach(button => {
                    button.addEventListener('click', function() {
                        document.getElementById('title').value = this.dataset.title;
                    });
                });
            }
        } catch (error) {
            console.error('Error generating titles:', error);
            alert('Failed to generate titles. Please try again.');
        }
    });

    // Generate Content
    generateContentBtn.addEventListener('click', async function() {
        const title = document.getElementById('title').value;
        const keywords = document.getElementById('keywords').value.split(',').map(k => k.trim());

        if (!title) {
            alert('Please enter a title first');
            return;
        }

        try {
            const response = await fetch('{{ route("content.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ title, keywords })
            });

            const data = await response.json();
            if (data.content) {
                document.getElementById('content').value = data.content;
            }
        } catch (error) {
            console.error('Error generating content:', error);
            alert('Failed to generate content. Please try again.');
        }
    });

    // Generate Full Post
    generateFullPostBtn.addEventListener('click', async function() {
        const topic = document.getElementById('topic').value;
        const keywords = document.getElementById('keywords').value.split(',').map(k => k.trim());

        if (!topic) {
            alert('Please enter a topic first');
            return;
        }

        try {
            const response = await fetch('{{ route("content.post") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ topic, keywords })
            });

            const data = await response.json();
            if (data.title && data.content) {
                document.getElementById('title').value = data.title;
                document.getElementById('content').value = data.content;
            }
        } catch (error) {
            console.error('Error generating post:', error);
            alert('Failed to generate post. Please try again.');
        }
    });
});
</script>
@endpush
@endsection
