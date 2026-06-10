<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBlocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_blocked) {
            $request->user()->tokens()->delete(); // Revoke tokens
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked by the administrator.',
                'data' => (object)[]
            ], 403);
        }
        return $next($request);
    }
}
