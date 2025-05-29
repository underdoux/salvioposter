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

class PostPublishNotificationsTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
        
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'blogger_post_id' => null
        ]);
    }

    #[Test]
    public function publishing_post_creates_notification(): void
    {
        // Bind a fake BloggerService
        $this->app->bind(BloggerService::class, function ($app) {
            return new class($this->user) extends BloggerService {
                public function __construct(User $user) {}
                
                public function createPost(Post $post): void
                {
                    $post->update([
                        'blogger_post_id' => '123',
                        'status' => 'published'
                    ]);
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $this->post));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->post->refresh();
        $this->assertEquals('published', $this->post->status);
        $this->assertEquals('123', $this->post->blogger_post_id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published'
        ]);
    }

    #[Test]
    public function publishing_failure_does_not_create_notification(): void
    {
        // Bind a fake BloggerService that fails
        $this->app->bind(BloggerService::class, function ($app) {
            return new class($this->user) extends BloggerService {
                public function __construct(User $user) {}
                
                public function createPost(Post $post): void
                {
                    throw new \Exception('Failed to connect to Blogger API');
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $this->post));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->post->refresh();
        $this->assertEquals('draft', $this->post->status);
        $this->assertNull($this->post->blogger_post_id);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published'
        ]);
    }

    #[Test]
    public function unauthorized_user_cannot_publish_or_create_notification(): void
    {
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        $response = $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->post(route('posts.publish', $this->post));

        $response->assertForbidden();

        $this->post->refresh();
        $this->assertEquals('draft', $this->post->status);
        $this->assertNull($this->post->blogger_post_id);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'type' => 'post_published'
        ]);
    }
}
