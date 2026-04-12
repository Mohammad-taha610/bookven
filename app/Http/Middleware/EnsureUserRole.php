<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        $allowed = array_filter(array_map('trim', explode('|', $roles)));

        if (! $user || ! in_array($user->role->value, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => (object) [],
                'errors' => (object) [],
            ], 403);
        }

        return $next($request);
    }
}
