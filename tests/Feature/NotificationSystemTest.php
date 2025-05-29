<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use Tests\Feature\Traits\WithOAuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
        $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
    }

    /** @test */
    public function it_creates_notification_when_post_is_created()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('posts.store'), [
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

    /** @test */
    public function it_creates_notification_when_post_is_updated()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson(route('posts.update', $post), [
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

    /** @test */
    public function it_creates_notification_when_post_is_deleted()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('posts.destroy', $post));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_deleted',
            'title' => 'Post Deleted',
            'read' => false
        ]);
    }

    /** @test */
    public function it_creates_notification_when_post_is_published()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('posts.publish', $post));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published',
            'read' => false
        ]);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', $notification));

        $response->assertOk();
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read' => true
        ]);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-all-read'));

        $response->assertOk();
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'read' => false
        ]);
    }

    /** @test */
    public function it_can_get_unread_notifications_count()
    {
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertOk()
            ->assertJson(['count' => 3]);
    }

    /** @test */
    public function it_can_clear_all_notifications()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.clear-all'));

        $response->assertOk();
        $this->assertDatabaseCount('notifications', 0);
    }

    /** @test */
    public function it_paginates_notifications()
    {
        Notification::factory()->count(15)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);
        
        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function it_only_shows_users_own_notifications()
    {
        $otherUser = User::factory()->create();
        
        Notification::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_others_notifications()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', $notification));

        $response->assertForbidden();
    }

    /** @test */
    public function it_can_get_recent_notifications()
    {
        Notification::factory()->count(10)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.recent'));

        $response->assertOk()
            ->assertJsonCount(5);
    }

    /** @test */
    public function it_can_update_email_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.preferences'), [
                'email_notifications' => true
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email_notifications' => true
        ]);
    }
}
