<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OAuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test Google credentials
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_redirect_to_google()
    {
        $response = $this->get('/auth/google');
        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_google_callback_with_invalid_state()
    {
        $response = $this->get('/auth/google/callback', ['error' => 'invalid_state']);
        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }

    public function test_protected_route_redirects_unauthorized_user()
    {
        // Test without authentication
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // Test with authentication but no OAuth token
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        // Create user with OAuth token
        $user = User::factory()->create();
        
        // Create valid OAuth token
        OAuthToken::create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => now()->addDay(),
        ]);

        // Mock the BloggerService
        $this->mock(\App\Services\BloggerService::class, function ($mock) {
            $mock->shouldReceive('refreshTokenIfNeeded')->andReturn(true);
        });

        // Test with both authentication and valid OAuth token
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
