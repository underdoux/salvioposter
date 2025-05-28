<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\Notification;
use App\Models\ScheduledPost;
use App\Policies\PostPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ScheduledPostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        Notification::class => NotificationPolicy::class,
        ScheduledPost::class => ScheduledPostPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Post-related gates
        Gate::define('publish-post', [PostPolicy::class, 'publish']);
        Gate::define('preview-post', [PostPolicy::class, 'view']);

        // Notification-related gates
        Gate::define('mark-notifications-read', [NotificationPolicy::class, 'markAllAsRead']);
        Gate::define('clear-notifications', [NotificationPolicy::class, 'clearAll']);
        Gate::define('update-notification-preferences', [NotificationPolicy::class, 'updatePreferences']);

        // Scheduled post-related gates
        Gate::define('schedule-post', [ScheduledPostPolicy::class, 'schedule']);
        Gate::define('view-failed-schedules', [ScheduledPostPolicy::class, 'viewFailed']);
    }
}
