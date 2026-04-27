<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        abort_if(!$user->role || !in_array($user->role->name, $roles, true), 403);

        return $next($request);
    }
}
