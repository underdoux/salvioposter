<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\PostAnalytics;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $post;
    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);
        $this->analyticsService = app(AnalyticsService::class);
    }

    public function test_can_track_post_views()
    {
        $analytics = PostAnalytics::factory()->create([
            'post_id' => $this->post->id,
            'views' => 100,
            'likes' => 10,
            'comments' => 5
        ]);

        $response = $this->actingAs($this->user)->get("/analytics/posts/{$this->post->id}");

        $response->assertStatus(200);
        $response->assertViewHas('analytics');
        $response->assertSee('100');  // Views count
        $response->assertSee('10');   // Likes count
        $response->assertSee('5');    // Comments count
    }

    public function test_can_export_analytics()
    {
        PostAnalytics::factory()->create([
            'post_id' => $this->post->id,
            'views' => 100,
            'likes' => 10,
            'comments' => 5
        ]);

        $response = $this->actingAs($this->user)->get("/analytics/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
    }

    public function test_analytics_updates_automatically()
    {
        $analytics = PostAnalytics::factory()->create([
            'post_id' => $this->post->id,
            'views' => 100
        ]);

        $this->artisan('analytics:sync');

        $this->assertDatabaseHas('post_analytics', [
            'post_id' => $this->post->id,
            'views' => 100
        ]);
    }

    public function test_can_view_analytics_dashboard()
    {
        PostAnalytics::factory()->count(3)->create([
            'post_id' => $this->post->id
        ]);

        $response = $this->actingAs($this->user)->get('/analytics');

        $response->assertStatus(200);
        $response->assertViewIs('analytics.index');
    }

    public function test_unauthorized_user_cannot_view_others_analytics()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)->get("/analytics/posts/{$this->post->id}");

        $response->assertStatus(403);
    }

    public function test_can_generate_performance_report()
    {
        PostAnalytics::factory()->create([
            'post_id' => $this->post->id,
            'views' => 100,
            'likes' => 10,
            'comments' => 5,
            'created_at' => Carbon::now()->subDays(7)
        ]);

        $response = $this->actingAs($this->user)
            ->get("/analytics/posts/{$this->post->id}/report");

        $response->assertStatus(200);
        $response->assertViewHas('report');
        $response->assertSee('Weekly Performance Report');
    }
}
