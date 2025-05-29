<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use Tests\Feature\Traits\WithOAuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
        $this->withHeaders(['Accept' => 'application/json']);
    }

    public function test_user_receives_notification_on_post_create()
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'title' => 'Test Post',
                'content' => 'Test Content'
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_created',
            'title' => 'Post Created',
            'read' => false
        ]);
    }

    public function test_user_receives_notification_on_post_update()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_updated',
            'title' => 'Post Updated',
            'read' => false
        ]);
    }

    public function test_user_receives_notification_on_post_delete()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('posts.destroy', $post));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_deleted',
            'title' => 'Post Deleted',
            'read' => false
        ]);
    }

    public function test_user_receives_notification_on_post_publish()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('posts.publish', $post));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published',
            'read' => false
        ]);
    }

    public function test_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.mark-read', $notification));

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read' => true,
            'read_at' => now()->toDateTimeString()
        ]);
    }

    public function test_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.mark-all-read'));

        $response->assertStatus(200);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'read' => false
        ]);
    }

    public function test_can_get_unread_notifications_count()
    {
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.unread-count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    public function test_can_clear_all_notifications()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.clear-all'));

        $response->assertStatus(200);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notifications_are_paginated()
    {
        Notification::factory()->count(15)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
    }

    public function test_user_only_sees_own_notifications()
    {
        $otherUser = User::factory()->create();
        
        Notification::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthorized_user_cannot_access_others_notifications()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.mark-read', $notification));

        $response->assertStatus(403);
    }

    public function test_can_get_recent_notifications()
    {
        Notification::factory()->count(10)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.recent'));

        $response->assertStatus(200);
        $response->assertJsonCount(5);
    }

    public function test_email_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->post(route('notifications.preferences'), [
                'email_notifications' => true
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email_notifications' => true
        ]);
    }
}
