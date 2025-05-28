<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\OAuthToken;
use App\Services\BloggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BloggerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'draft'
        ]);
    }

    public function test_user_can_create_post()
    {
        $response = $this->actingAs($this->user)->post('/posts', [
            'title' => 'New Test Post',
            'content' => 'New Test Content'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'title' => 'New Test Post',
            'content' => 'New Test Content'
        ]);
    }

    public function test_user_can_update_post()
    {
        $response = $this->actingAs($this->user)->put("/posts/{$this->post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ]);
    }

    public function test_user_can_delete_post()
    {
        $response = $this->actingAs($this->user)->delete("/posts/{$this->post->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('posts', [
            'id' => $this->post->id
        ]);
    }

    public function test_unauthorized_user_cannot_modify_others_post()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)->put("/posts/{$this->post->id}", [
            'title' => 'Unauthorized Update'
        ]);

        $response->assertStatus(403);
    }

    public function test_can_publish_to_blogger()
    {
        $token = OAuthToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => now()->addHour()
        ]);

        $response = $this->actingAs($this->user)
            ->post("/posts/{$this->post->id}/publish");

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'status' => 'published'
        ]);
    }
}
