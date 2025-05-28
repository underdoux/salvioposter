<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->notificationService = app(NotificationService::class);
    }

    public function test_user_receives_notification_on_post_publish()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user)
            ->post("/posts/{$post->id}/publish");

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'read' => false
        ]);
    }

    public function test_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read' => false
        ]);

        $response = $this->actingAs($this->user)
            ->post("/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read' => true
        ]);
    }

    public function test_can_get_unread_notifications_count()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read' => false
        ]);

        $response = $this->actingAs($this->user)
            ->get('/notifications/unread/count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    public function test_can_clear_all_notifications()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/notifications');

        $response->assertStatus(200);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notifications_are_paginated()
    {
        Notification::factory()->count(15)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get('/notifications');

        $response->assertStatus(200);
        $response->assertViewHas('notifications');
        $this->assertCount(10, $response->viewData('notifications')); // Assuming 10 per page
    }

    public function test_user_only_sees_own_notifications()
    {
        $otherUser = User::factory()->create();
        
        Notification::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->get('/notifications');

        $response->assertStatus(200);
        $response->assertViewHas('notifications');
        $this->assertCount(0, $response->viewData('notifications'));
    }

    public function test_email_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->post('/notifications/preferences', [
                'email_notifications' => true
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email_notifications' => true
        ]);
    }
}
