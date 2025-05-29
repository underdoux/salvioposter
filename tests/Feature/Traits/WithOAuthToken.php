<?php

namespace Tests\Feature\Traits;

use App\Models\OAuthToken;

trait WithOAuthToken
{
    protected function setupOAuthToken($user)
    {
        return OAuthToken::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addDay(),
        ]);
    }
}
