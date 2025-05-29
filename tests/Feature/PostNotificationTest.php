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
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class PostNotificationTest extends TestCase
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
    public function publishing_a_post_creates_notification(): void
    {
        $this->withoutExceptionHandling();

        // Create a mock BloggerService
        $mockBloggerService = new class {
            public function createPost($post) { return true; }
            public function updatePost($post) { return true; }
        };
        $this->app->instance(BloggerService::class, $mockBloggerService);

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'blogger_post_id' => null
        ]);

        // Attempt to publish the post
        try {
            $response = $this->actingAs($this->user)
                ->withHeaders($this->getOAuthHeaders())
                ->post(route('posts.publish', $post));

            // Log response content for debugging
            Log::info('Response content:', [
                'status' => $response->status(),
                'content' => $response->content()
            ]);

            // Check if the post was updated
            $post->refresh();
            $this->assertEquals('published', $post->status);

            // Check if notification was created
            $notification = Notification::where([
                'user_id' => $this->user->id,
                'type' => 'post_published'
            ])->first();

            $this->assertNotNull($notification, 'No notification was created');
            $this->assertEquals('Post Published', $notification->title);
            $this->assertFalse($notification->read);
            $this->assertEquals($post->id, $notification->data['post_id']);

        } catch (\Exception $e) {
            $this->fail('Failed to publish post: ' . $e->getMessage());
        }
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
