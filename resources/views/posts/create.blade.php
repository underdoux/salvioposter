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

                <!-- AI Content Generation -->
                <div class="bg-gray-50 p-4 rounded-md">
                    <h3 class="text-sm font-medium text-gray-900">Need inspiration?</h3>
                    <div class="mt-2 flex gap-2">
                        <button type="button" id="generateTitle" 
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            Generate Title
                        </button>
                        <button type="button" id="generateContent" 
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            Generate Content
                        </button>
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
    // Simple content generation preview (to be implemented with AI later)
    document.getElementById('generateTitle').addEventListener('click', function() {
        // This is a placeholder. Will be replaced with actual AI generation
        const titles = [
            'How to Master Your Craft',
            '10 Tips for Better Productivity',
            'The Ultimate Guide to Success'
        ];
        document.getElementById('title').value = titles[Math.floor(Math.random() * titles.length)];
    });

    document.getElementById('generateContent').addEventListener('click', function() {
        // This is a placeholder. Will be replaced with actual AI generation
        const content = `# Introduction\n\nThis is a sample blog post content.\n\n## Main Points\n\n1. First important point\n2. Second important point\n3. Third important point\n\n## Conclusion\n\nThank you for reading!`;
        document.getElementById('content').value = content;
    });
</script>
@endpush
@endsection
