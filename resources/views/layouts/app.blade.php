<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Blogspot Auto Poster') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Mobile menu button -->
                <div class="flex items-center sm:hidden">
                    <button type="button" 
                            class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                            aria-controls="mobile-menu" 
                            aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-800">
                            Blogspot Auto Poster
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('posts.index') }}" 
                           class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Posts
                        </a>
                        <a href="{{ route('scheduled.index') }}" 
                           class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Scheduled Posts
                        </a>
                        <a href="{{ route('analytics.index') }}" 
                           class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Analytics
                        </a>
                        <a href="{{ route('posts.create') }}" 
                           class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Create Post
                        </a>
                    </div>
                </div>

                @auth
                    <div class="hidden sm:flex sm:items-center space-x-4">
                        <!-- Notifications Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span id="notification-badge" 
                                      class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full {{ auth()->user()->notifications()->unread()->count() ? '' : 'hidden' }}">
                                    {{ auth()->user()->notifications()->unread()->count() }}
                                </span>
                            </button>

                            <div x-show="open"
                                 @click.away="open = false"
                                 class="absolute right-0 w-80 mt-2 bg-white rounded-md shadow-lg overflow-hidden z-50"
                                 style="display: none;">
                                <div class="py-2">
                                    <div class="px-4 py-2 border-b border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                            <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 hover:text-indigo-900">
                                                View All
                                            </a>
                                        </div>
                                    </div>
                                    <div id="notification-list" class="max-h-64 overflow-y-auto">
                                        <!-- Notifications will be loaded here via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <span class="text-gray-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                Logout
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Mobile menu -->
    <div class="sm:hidden hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('posts.index') }}" 
               class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                Posts
            </a>
            <a href="{{ route('scheduled.index') }}" 
               class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                Scheduled Posts
            </a>
            <a href="{{ route('posts.create') }}" 
               class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                Create Post
            </a>
        </div>
    </div>

    <main class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @stack('scripts')
    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                    const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
                    mobileMenuButton.setAttribute('aria-expanded', !expanded);
                });
            }

            // Load notifications dynamically
            const notificationList = document.getElementById('notification-list');
            const notificationBadge = document.getElementById('notification-badge');

            async function loadNotifications() {
                try {
                    const response = await fetch('{{ route("notifications.recent") }}');
                    const data = await response.json();

                    if (data.notifications && notificationList) {
                        notificationList.innerHTML = '';
                        data.notifications.forEach(notification => {
                            const div = document.createElement('div');
                            div.className = `p-4 border-b border-gray-200 ${notification.read_at ? '' : 'bg-indigo-50'}`;
                            div.innerHTML = `
                                <div class="flex justify-between items-center">
                                    <p class="text-sm font-medium">${notification.title}</p>
                                    <small class="text-xs text-gray-500">${new Date(notification.created_at).toLocaleString()}</small>
                                </div>
                                <p class="text-sm">${notification.message}</p>
                            `;
                            notificationList.appendChild(div);
                        });
                    }

                    if (notificationBadge) {
                        notificationBadge.textContent = data.unread_count || 0;
                        notificationBadge.classList.toggle('hidden', !data.unread_count);
                    }
                } catch (error) {
                    console.error('Failed to load notifications:', error);
                }
            }

            loadNotifications();

            // Optionally, refresh notifications every minute
            setInterval(loadNotifications, 60000);
        });
    </script>
</body>
</html>
