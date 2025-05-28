<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_google()
    {
        $response = $this->get('/auth/google');
        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_google_callback_with_invalid_state()
    {
        $response = $this->get('/auth/google/callback');
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function test_protected_route_redirects_unauthorized_user()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
