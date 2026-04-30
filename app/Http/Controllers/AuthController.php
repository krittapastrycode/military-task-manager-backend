<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required|string',
            'rank' => 'nullable|string',
            'unit' => 'nullable|string',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'name' => $validated['name'],
            'rank' => $validated['rank'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'role' => 'user',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function googleRedirect(): JsonResponse
    {
        $params = http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'offline',
            'prompt'        => 'select_account',
        ]);

        return response()->json([
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?' . $params,
        ]);
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $code = $request->input('code');

        if (!$code) {
            return redirect($frontendUrl . '/?error=google_auth_cancelled');
        }

        $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => config('services.google.redirect_uri'),
            'grant_type'    => 'authorization_code',
        ]);

        if ($tokenRes->failed()) {
            Log::error('Google OAuth token exchange failed', $tokenRes->json() ?? []);
            return redirect($frontendUrl . '/?error=google_token_failed');
        }

        $userInfoRes = Http::withToken($tokenRes->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($userInfoRes->failed()) {
            Log::error('Google OAuth userinfo failed', $userInfoRes->json() ?? []);
            return redirect($frontendUrl . '/?error=google_userinfo_failed');
        }

        $googleUser = $userInfoRes->json();

        $user = User::where('google_id', $googleUser['sub'])
            ->orWhere('email', $googleUser['email'])
            ->first();

        if ($user) {
            $user->update([
                'google_id'  => $googleUser['sub'],
                'avatar_url' => $user->avatar_url ?? ($googleUser['picture'] ?? null),
            ]);
        } else {
            $user = User::create([
                'email'          => $googleUser['email'],
                'name'           => $googleUser['name'],
                'google_id'      => $googleUser['sub'],
                'avatar_url'     => $googleUser['picture'] ?? null,
                'email_verified' => true,
                'role'           => 'user',
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $profile = urlencode(json_encode([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'avatar_url' => $user->avatar_url,
            'role'       => $user->role,
            'rank'       => $user->rank,
            'unit'       => $user->unit,
        ]));

        return redirect($frontendUrl . '/auth/callback?token=' . $token . '&profile=' . $profile);
    }
}
