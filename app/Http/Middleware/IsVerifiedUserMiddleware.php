<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsVerifiedUserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user()->is_verified) {
            return response()->json([
                'message' => 'user not verified'
            ], 403);
        }
        return $next($request);
    }
}
