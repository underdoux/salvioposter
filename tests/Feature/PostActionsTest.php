<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use Tests\Feature\Traits\WithOAuthToken;
use Tests\Feature\Traits\WithOAuthHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostActionsTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
    }

    /** @test */
    public function it_creates_notification_when_post_is_created()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.store'), [
                'title' => 'Test Post',
                'content' => 'Test Content'
            ]);

        $response->assertSuccessful();

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
            ->withHeaders($this->getOAuthHeaders())
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ]);

        $response->assertSuccessful();

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
        $title = $post->title;

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->delete(route('posts.destroy', $post));

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_deleted',
            'title' => 'Post Deleted',
            'read' => false
        ]);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id
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
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $post));

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published',
            'read' => false
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published'
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_updating_others_posts()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        $response = $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'type' => 'post_updated'
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_deleting_others_posts()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        $response = $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->delete(route('posts.destroy', $post));

        $response->assertForbidden();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'type' => 'post_deleted'
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_publishing_others_posts()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        $response = $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->post(route('posts.publish', $post));

        $response->assertForbidden();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'type' => 'post_published'
        ]);
    }
}
