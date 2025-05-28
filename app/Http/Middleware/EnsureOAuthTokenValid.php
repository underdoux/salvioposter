<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOAuthTokenValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Check if user has OAuth token
        if (!$user->oauthToken) {
            return redirect()->route('auth.google');
        }

        // Check if token is expired or will expire soon
        if ($user->oauthToken->hasExpired() || $user->oauthToken->willExpireSoon()) {
            try {
                // Attempt to refresh the token
                event(new \App\Events\OAuthTokenRefreshRequired($user));
                
                // If token refresh fails, redirect to re-authenticate
                if (!$user->hasValidOAuthToken()) {
                    return redirect()->route('auth.google');
                }
            } catch (\Exception $e) {
                return redirect()->route('auth.google')
                    ->with('error', 'Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
