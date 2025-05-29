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
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase, WithOAuthToken, WithOAuthHeaders;

    protected User $user;
    protected $bloggerService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user and setup OAuth token
        $this->user = User::factory()->create();
        $this->setupOAuthToken($this->user);
    }

    #[Test]
    public function publishing_post_creates_notification(): void
    {
        // Create a draft post
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'blogger_post_id' => null
        ]);

        // Mock BloggerService
        $this->mock(BloggerService::class, function ($mock) use ($post) {
            $mock->shouldReceive('createPost')
                ->once()
                ->with(Mockery::on(function($arg) use ($post) {
                    return $arg->id === $post->id;
                }))
                ->andReturnUsing(function($post) {
                    $post->update([
                        'blogger_post_id' => '123',
                        'status' => 'published'
                    ]);
                });
        });

        // Make the request
        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $post));

        // Assert response redirects
        $response->assertRedirect();

        // Assert post was published
        $post->refresh();
        $this->assertEquals('published', $post->status);
        $this->assertEquals('123', $post->blogger_post_id);

        // Assert notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published',
            'title' => 'Post Published'
        ]);
    }

    #[Test]
    public function publishing_fails_gracefully_on_error(): void
    {
        // Create a draft post
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'blogger_post_id' => null
        ]);

        // Mock BloggerService to throw exception
        $this->mock(BloggerService::class, function ($mock) {
            $mock->shouldReceive('createPost')
                ->once()
                ->andThrow(new \Exception('Failed to connect to Blogger API'));
        });

        // Make the request
        $response = $this->actingAs($this->user)
            ->withHeaders($this->getOAuthHeaders())
            ->post(route('posts.publish', $post));

        // Assert response redirects back with error
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Assert post remains in draft
        $post->refresh();
        $this->assertEquals('draft', $post->status);
        $this->assertNull($post->blogger_post_id);

        // Assert no publish notification was created
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'type' => 'post_published'
        ]);
    }

    #[Test]
    public function unauthorized_user_cannot_publish_post(): void
    {
        // Create a post owned by our main user
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        // Create another user
        $otherUser = User::factory()->create();
        $this->setupOAuthToken($otherUser);

        // Attempt to publish as other user
        $response = $this->actingAs($otherUser)
            ->withHeaders($this->getOAuthHeaders($otherUser))
            ->post(route('posts.publish', $post));

        // Assert forbidden
        $response->assertForbidden();

        // Assert post remains in draft
        $post->refresh();
        $this->assertEquals('draft', $post->status);

        // Assert no notification was created
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'type' => 'post_published'
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
