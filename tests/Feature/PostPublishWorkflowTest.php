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
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;

class PostPublishWorkflowTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
        
        // Create a draft post
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'blogger_post_id' => null
        ]);
    }

    #[Test]
    public function successful_post_publishing_creates_notification(): void
    {
        // Bind a fake BloggerService that simulates successful publishing
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

        $response->assertRedirect(route('posts.show', $this->post));
        $response->assertSessionHas('success', 'Post published to Blogger successfully!');

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
    public function failed_publishing_does_not_create_notification(): void
    {
        // Bind a fake BloggerService that simulates failure
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
    public function unauthorized_user_cannot_publish_post(): void
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
