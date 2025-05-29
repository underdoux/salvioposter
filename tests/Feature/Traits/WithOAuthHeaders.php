<?php

namespace Tests\Feature\Traits;

trait WithOAuthHeaders
{
    protected function getOAuthHeaders($user = null)
    {
        $user = $user ?? $this->user;
        
        return [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'Authorization' => 'Bearer ' . $user->oauthToken->access_token
        ];
    }
}
