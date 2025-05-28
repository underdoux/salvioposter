<?php

namespace App\Policies;

use App\Models\ScheduledPost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledPostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }

    /**
     * Determine whether the user can retry a failed scheduled post.
     */
    public function retry(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }

    /**
     * Determine whether the user can view failed scheduled posts.
     */
    public function viewFailed(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can schedule a post.
     */
    public function schedule(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can reschedule a post.
     */
    public function reschedule(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }

    /**
     * Determine whether the user can cancel a scheduled post.
     */
    public function cancel(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->post->user_id;
    }
}
