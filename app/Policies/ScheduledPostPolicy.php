<?php

namespace App\Policies;

use App\Models\ScheduledPost;
use App\Models\User;

class ScheduledPostPolicy
{
    /**
     * Determine whether the user can view any scheduled posts.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the scheduled post.
     */
    public function view(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->user_id;
    }

    /**
     * Determine whether the user can create scheduled posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the scheduled post.
     */
    public function update(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->user_id;
    }

    /**
     * Determine whether the user can delete the scheduled post.
     */
    public function delete(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->user_id;
    }

    /**
     * Determine whether the user can retry the scheduled post.
     */
    public function retry(User $user, ScheduledPost $scheduledPost): bool
    {
        return $user->id === $scheduledPost->user_id && 
               $scheduledPost->status === 'failed' && 
               $scheduledPost->retry_count < 3;
    }
}
