<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRoles = (array) ($request->user()?->role ?? []);
        if (!$request->user() || empty(array_intersect($userRoles, $roles))) {
            return response()->json(['message' => 'ไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        return $next($request);
    }
}
