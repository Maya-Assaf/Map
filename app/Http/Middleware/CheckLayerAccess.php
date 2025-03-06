<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLayerAccess
{
    public function handle(Request $request, Closure $next, $layer)
    {
        $user = Auth::user();

        // Check if the user has access to the specified layer
        if ($user->layer !== $layer && !in_array($user->role_id, [1, 2])) { // Assuming role_id 1 and 2 are Head and CoHead
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}