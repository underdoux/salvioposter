<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidOAuthToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Please log in to continue.');
        }

        if (!$request->user()->oauthToken) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Please log in with Google to continue.');
        }

        if ($request->user()->oauthToken->hasExpired()) {
            try {
                $bloggerService = app(\App\Services\BloggerService::class);
                $bloggerService->refreshTokenIfNeeded($request->user());
            } catch (\Exception $e) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
