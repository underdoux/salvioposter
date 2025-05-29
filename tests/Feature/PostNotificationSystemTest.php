<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use App\Services\BloggerService;
use Tests\Feature\Traits\WithOAuthToken;
use Tests\Feature\Traits\WithOAuthHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PostNotificationSystemTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
    }

    #[Test]
    public function post_creation_generates_notification(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.store'), [
                'title' => 'Test Post',
                'content' => 'Test Content'
            ]);

        $response->assertRedirect(route('posts.edit', Post::first()));
        $response->assertSessionHas('success', 'Post created successfully!');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_created',
            'title' => 'Post Created',
            'read' => false
        ]);

        $post = Post::where('title', 'Test Post')->first();
        $this->assertNotNull($post);
    }

    #[Test]
    public function post_update_generates_notification(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ]);

        $response->assertRedirect(route('posts.edit', $post));
        $response->assertSessionHas('success', 'Post updated successfully!');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_updated',
            'title' => 'Post Updated',
            'read' => false
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ]);
    }

    #[Test]
    public function post_deletion_generates_notification(): void
    {
        $this->withoutExceptionHandling();
        
        // Create a post without blogger_post_id to avoid BloggerService call
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'blogger_post_id' => null
        ]);
        $title = $post->title;

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('posts.index'));
        $response->assertSessionHas('success', 'Post deleted successfully!');

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

    #[Test]
    public function post_publishing_generates_notification(): void
    {
        $this->withoutExceptionHandling();
        
        // Mock BloggerService
        $this->app->instance(BloggerService::class, new class {
            public function createPost($post) { return true; }
            public function updatePost($post) { return true; }
        });

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $post));

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('success', 'Post published to Blogger successfully!');

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

    #[Test]
    public function unauthorized_users_cannot_update_others_posts(): void
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

    #[Test]
    public function unauthorized_users_cannot_delete_others_posts(): void
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

    #[Test]
    public function unauthorized_users_cannot_publish_others_posts(): void
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
