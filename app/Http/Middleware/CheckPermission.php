<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::user()->permissions->contains('name', $permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}