<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OAuthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Client;
use Carbon\Carbon;

class GoogleAuthController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope('https://www.googleapis.com/auth/blogger');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent'); // Force to get refresh token
    }

    /**
     * Redirect the user to Google's authentication page.
     */
    public function redirectToGoogle()
    {
        return redirect($this->client->createAuthUrl());
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')
                ->with('error', 'Google authentication failed: ' . $request->get('error'));
        }

        try {
            // Exchange authorization code for access token
            $token = $this->client->fetchAccessTokenWithAuthCode($request->get('code'));

            if (!isset($token['access_token'])) {
                return redirect()->route('login')
                    ->with('error', 'Failed to obtain access token from Google.');
            }

            // Get user info from Google
            $this->client->setAccessToken($token);
            $google_oauth = new \Google_Service_Oauth2($this->client);
            $google_user = $google_oauth->userinfo->get();

            // Find or create user
            $user = User::updateOrCreate(
                ['email' => $google_user->email],
                [
                    'name' => $google_user->name,
                    'password' => bcrypt(str_random(16)), // Random password for OAuth users
                ]
            );

            // Store or update OAuth tokens
            OAuthToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'token_type' => $token['token_type'] ?? 'Bearer',
                    'expires_at' => Carbon::now()->addSeconds($token['expires_in']),
                ]
            );

            // Log the user in
            Auth::login($user);

            return redirect()->route('dashboard')
                ->with('success', 'Successfully logged in with Google!');

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'An error occurred during authentication: ' . $e->getMessage());
        }
    }

    /**
     * Refresh the access token using the refresh token.
     */
    public function refreshToken(User $user)
    {
        try {
            $oauth_token = $user->oauthToken;

            if (!$oauth_token || !$oauth_token->refresh_token) {
                throw new \Exception('No refresh token available.');
            }

            $this->client->setAccessToken([
                'access_token' => $oauth_token->access_token,
                'refresh_token' => $oauth_token->refresh_token,
                'token_type' => $oauth_token->token_type,
                'expires_in' => Carbon::now()->diffInSeconds($oauth_token->expires_at),
            ]);

            if ($this->client->isAccessTokenExpired()) {
                $new_token = $this->client->fetchAccessTokenWithRefreshToken();

                if (!isset($new_token['access_token'])) {
                    throw new \Exception('Failed to refresh access token.');
                }

                $oauth_token->update([
                    'access_token' => $new_token['access_token'],
                    'refresh_token' => $new_token['refresh_token'] ?? $oauth_token->refresh_token,
                    'expires_at' => Carbon::now()->addSeconds($new_token['expires_in']),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Token refresh failed: ' . $e->getMessage());
            return false;
        }
    }
}
