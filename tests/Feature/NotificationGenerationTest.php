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

class NotificationGenerationTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
    }

    #[Test]
    public function creating_a_post_generates_notification(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.store'), [
                'title' => 'Test Post',
                'content' => 'Test Content'
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_created',
            'title' => 'Post Created'
        ]);
    }

    #[Test]
    public function updating_a_post_generates_notification(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_updated',
            'title' => 'Post Updated'
        ]);
    }

    #[Test]
    public function deleting_a_post_generates_notification(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'blogger_post_id' => null
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->delete(route('posts.destroy', $post));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_deleted',
            'title' => 'Post Deleted'
        ]);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function publishing_a_post_generates_notification(): void
    {
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

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published'
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published'
        ]);
    }

    #[Test]
    public function unauthorized_users_cannot_generate_notifications(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        // Try to update
        $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ])
            ->assertForbidden();

        // Try to delete
        $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();

        // Try to publish
        $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->post(route('posts.publish', $post))
            ->assertForbidden();

        // Verify no notifications were created for unauthorized user
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id
        ]);
    }
}
