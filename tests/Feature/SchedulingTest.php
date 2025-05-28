<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Services\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $post;
    protected $schedulingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);
        $this->schedulingService = app(SchedulingService::class);
    }

    public function test_can_schedule_post()
    {
        $scheduledTime = Carbon::now()->addHours(24);
        
        $response = $this->actingAs($this->user)->post("/posts/{$this->post->id}/schedule", [
            'scheduled_at' => $scheduledTime
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('scheduled_posts', [
            'post_id' => $this->post->id,
            'scheduled_at' => $scheduledTime->format('Y-m-d H:i:s')
        ]);
    }

    public function test_cannot_schedule_in_past()
    {
        $pastTime = Carbon::now()->subHour();
        
        $response = $this->actingAs($this->user)->post("/posts/{$this->post->id}/schedule", [
            'scheduled_at' => $pastTime
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_schedule()
    {
        $scheduledPost = ScheduledPost::factory()->create([
            'post_id' => $this->post->id,
            'scheduled_at' => Carbon::now()->addDay()
        ]);

        $newTime = Carbon::now()->addDays(2);
        
        $response = $this->actingAs($this->user)->put("/scheduled-posts/{$scheduledPost->id}", [
            'scheduled_at' => $newTime
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('scheduled_posts', [
            'id' => $scheduledPost->id,
            'scheduled_at' => $newTime->format('Y-m-d H:i:s')
        ]);
    }

    public function test_can_cancel_schedule()
    {
        $scheduledPost = ScheduledPost::factory()->create([
            'post_id' => $this->post->id,
            'scheduled_at' => Carbon::now()->addDay()
        ]);

        $response = $this->actingAs($this->user)->delete("/scheduled-posts/{$scheduledPost->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('scheduled_posts', [
            'id' => $scheduledPost->id
        ]);
    }

    public function test_scheduled_post_publishes_automatically()
    {
        $scheduledPost = ScheduledPost::factory()->create([
            'post_id' => $this->post->id,
            'scheduled_at' => Carbon::now()
        ]);

        $this->artisan('posts:publish-scheduled');

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'status' => 'published'
        ]);

        $this->assertDatabaseMissing('scheduled_posts', [
            'id' => $scheduledPost->id
        ]);
    }
}
