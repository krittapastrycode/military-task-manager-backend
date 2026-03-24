<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'rank' => 'nullable|string',
            'position' => 'nullable|string',
            'unit' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json($user);
    }
}
