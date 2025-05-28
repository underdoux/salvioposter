@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900">Notifications</h2>
                <div class="flex space-x-4">
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Mark All as Read
                        </button>
                    </form>
                    <form action="{{ route('notifications.clear-all') }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                                onclick="return confirm('Are you sure you want to clear all notifications?')">
                            Clear All
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse($notifications as $notification)
                <div class="p-6 {{ $notification->bg_color_class }} {{ $notification->isUnread() ? 'border-l-4 border-indigo-500' : '' }}"
                     id="notification-{{ $notification->id }}">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <i class="{{ $notification->icon_class }} text-2xl"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between">
                                <h3 class="text-sm font-medium text-gray-900">{{ $notification->title }}</h3>
                                <p class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">{{ $notification->message }}</p>
                            
                            @if($notification->data)
                                <div class="mt-2">
                                    @if(isset($notification->data['post_id']))
                                        <a href="{{ route('posts.show', $notification->data['post_id']) }}" 
                                           class="text-sm text-indigo-600 hover:text-indigo-900">
                                            View Post
                                        </a>
                                    @endif

                                    @if(isset($notification->data['metrics']))
                                        <div class="mt-2 grid grid-cols-3 gap-4">
                                            @foreach($notification->data['metrics'] as $metric => $value)
                                                <div class="text-sm">
                                                    <dt class="font-medium text-gray-500">{{ ucfirst($metric) }}</dt>
                                                    <dd class="mt-1 text-gray-900">+{{ number_format($value) }}</dd>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($notification->data['error']))
                                        <p class="mt-2 text-sm text-red-600">
                                            Error: {{ $notification->data['error'] }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex-shrink-0 flex space-x-3">
                            @if($notification->isUnread())
                                <button type="button"
                                        onclick="markAsRead('{{ $notification->id }}')"
                                        class="text-sm text-indigo-600 hover:text-indigo-900">
                                    Mark as Read
                                </button>
                            @endif
                            <button type="button"
                                    onclick="deleteNotification('{{ $notification->id }}')"
                                    class="text-sm text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center">
                    <p class="text-gray-500">No notifications found.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function markAsRead(id) {
    fetch(`/notifications/${id}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            const notification = document.getElementById(`notification-${id}`);
            notification.classList.remove('border-l-4', 'border-indigo-500');
            notification.querySelector('button').remove();
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteNotification(id) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }

    fetch(`/notifications/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            const notification = document.getElementById(`notification-${id}`);
            notification.remove();
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateUnreadCount() {
    fetch('/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.classList.toggle('hidden', data.count === 0);
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>
@endpush
@endsection
