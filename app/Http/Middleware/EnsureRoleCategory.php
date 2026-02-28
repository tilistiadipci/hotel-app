<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleCategory
{
    /**
     * Ensure the authenticated user's role category is allowed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$categories
     */
    public function handle(Request $request, Closure $next, string ...$categories): Response
    {
        $user = $request->user();

        if (!$user || !$user->role || empty($categories)) {
            abort(403);
        }

        if (!in_array($user->role->category, $categories, true)) {
            abort(403);
        }

        return $next($request);
    }
}
