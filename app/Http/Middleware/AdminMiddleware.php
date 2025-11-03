<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
         // Check if user is authenticated
         if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login first.'
            ], 401);
        }

        // Check if user has admin privileges
        $user = auth()->user();
        
      // التحقق من ان المستخدم في قسم الادمن
    if ($user->department !== 'ADMIN DEPARTMENT') {
        return response()->json([
            'message' => 'Unauthorized. Admin access required.'
        ], 403);
    }

    return $next($request);
}
}
