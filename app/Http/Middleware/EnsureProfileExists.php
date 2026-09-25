<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->profile) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please complete your profile first.'], 403);
            }

            return redirect()->route('profile.create')->with('info', 'Please complete your profile to continue.');
        }

        return $next($request);
    }
}
