<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestPasswordUserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->rest_password) {
            return response()->json([
                'message' => 'The password must be changed.'
            ], 405);
        }
        return $next($request);
    }
}
