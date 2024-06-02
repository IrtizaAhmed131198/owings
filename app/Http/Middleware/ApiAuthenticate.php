<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('sanctum')->check()) {
            // User is authenticated, proceed with the request
            return $next($request);
        }

        // User is not authenticated, return a custom JSON response
        return response()->json(['message' => 'You need login first'], 401);
    }
}
