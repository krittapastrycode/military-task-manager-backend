<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user || !collect($roles)->contains(fn($r) => $user->hasRole($r))) {
            return response()->json(['message' => 'ไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        return $next($request);
    }
}
