@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Scheduled Posts</h1>
            <p class="mt-2 text-sm text-gray-700">A list of all your scheduled blog posts and their current status.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-8 flex flex-col">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Title</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Scheduled For</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Attempt</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($scheduledPosts as $scheduled)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        {{ $scheduled->post->title }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $scheduled->formatted_scheduled_date }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $scheduled->status_badge_class }}">
                                            {{ ucfirst($scheduled->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $scheduled->last_attempt_at ? $scheduled->last_attempt_at->diffForHumans() : 'Not attempted' }}
                                        @if($scheduled->status === 'failed')
                                            <span class="block text-xs text-red-600">{{ $scheduled->failure_reason }}</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        @if($scheduled->status !== 'completed')
                                            <button 
                                                onclick="document.getElementById('edit-modal-{{ $scheduled->id }}').classList.remove('hidden')"
                                                class="text-indigo-600 hover:text-indigo-900 mr-4">
                                                Edit
                                            </button>
                                            
                                            <form action="{{ route('scheduled.destroy', $scheduled) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Cancel
                                                </button>
                                            </form>

                                            @if($scheduled->status === 'failed')
                                                <form action="{{ route('scheduled.retry', $scheduled) }}" method="POST" class="inline ml-4">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">
                                                        Retry
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div id="edit-modal-{{ $scheduled->id }}" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
                                    <div class="bg-white rounded-lg p-8 max-w-md w-full">
                                        <h3 class="text-lg font-medium mb-4">Reschedule Post</h3>
                                        <form action="{{ route('scheduled.update', $scheduled) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="mb-4">
                                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700">New Schedule Time</label>
                                                <input type="datetime-local" 
                                                       name="scheduled_at" 
                                                       id="scheduled_at" 
                                                       value="{{ $scheduled->scheduled_at->format('Y-m-d\TH:i') }}"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div class="flex justify-end space-x-3">
                                                <button type="button" 
                                                        onclick="this.closest('[id^=edit-modal]').classList.add('hidden')"
                                                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                        class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                                        No scheduled posts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $scheduledPosts->links() }}
    </div>
</div>
@endsection
