<?php

namespace App\Http\Controllers;

use App\Models\GoogleCalendarToken;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CalendarController extends Controller
{
    private function getOAuthConfig(): array
    {
        return [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'token_url' => 'https://oauth2.googleapis.com/token',
            'calendar_url' => 'https://www.googleapis.com/calendar/v3',
        ];
    }

    public function authUrl(Request $request): JsonResponse
    {
        $config = $this->getOAuthConfig();
        $state = $request->user()->id;
        $scope = urlencode('https://www.googleapis.com/auth/calendar');

        $url = "https://accounts.google.com/o/oauth2/v2/auth?"
            . "client_id={$config['client_id']}"
            . "&redirect_uri={$config['redirect_uri']}"
            . "&response_type=code"
            . "&scope={$scope}"
            . "&access_type=offline"
            . "&prompt=consent"
            . "&state={$state}";

        return response()->json(['auth_url' => $url]);
    }

    public function callback(Request $request): JsonResponse
    {
        $code = $request->query('code');
        $state = $request->query('state'); // user_id

        if (!$code) {
            return response()->json(['error' => 'No code provided'], 400);
        }

        $config = $this->getOAuthConfig();

        // Exchange code for token
        $response = Http::asForm()->post($config['token_url'], [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to exchange token'], 500);
        }

        $tokenData = $response->json();

        // Save token to database
        $calToken = GoogleCalendarToken::firstOrNew(['user_id' => $state]);
        $calToken->access_token = $tokenData['access_token'];
        $calToken->refresh_token = $tokenData['refresh_token'] ?? $calToken->refresh_token;
        $calToken->token_type = $tokenData['token_type'] ?? 'Bearer';
        $calToken->expiry = Carbon::now()->addSeconds($tokenData['expires_in'] ?? 3600);
        $calToken->scope = $tokenData['scope'] ?? 'https://www.googleapis.com/auth/calendar';
        $calToken->save();

        return response()->json(['message' => 'Calendar connected successfully']);
    }

    private function getAccessToken(GoogleCalendarToken $calToken): ?string
    {
        // Refresh if expired
        if (Carbon::parse($calToken->expiry)->isPast()) {
            $config = $this->getOAuthConfig();
            $response = Http::asForm()->post($config['token_url'], [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $calToken->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                return null;
            }

            $tokenData = $response->json();
            $calToken->access_token = $tokenData['access_token'];
            $calToken->expiry = Carbon::now()->addSeconds($tokenData['expires_in'] ?? 3600);
            $calToken->save();
        }

        return $calToken->access_token;
    }

    public function events(Request $request): JsonResponse
    {
        $user = $request->user();

        $calToken = GoogleCalendarToken::where('user_id', $user->id)->first();
        if (!$calToken) {
            return response()->json(['error' => 'Calendar not connected'], 404);
        }

        $accessToken = $this->getAccessToken($calToken);
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to refresh token'], 500);
        }

        $config = $this->getOAuthConfig();
        $now = Carbon::now()->toRfc3339String();
        $weekLater = Carbon::now()->addDays(7)->toRfc3339String();

        $response = Http::withToken($accessToken)->get("{$config['calendar_url']}/calendars/primary/events", [
            'timeMin' => $now,
            'timeMax' => $weekLater,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch events'], 500);
        }

        return response()->json($response->json('items', []));
    }

    public function syncTask(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'task_id' => 'required|uuid',
        ]);

        $task = Task::where('id', $request->input('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $calToken = GoogleCalendarToken::where('user_id', $user->id)->first();
        if (!$calToken) {
            return response()->json(['error' => 'Calendar not connected'], 404);
        }

        $accessToken = $this->getAccessToken($calToken);
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to refresh token'], 500);
        }

        $config = $this->getOAuthConfig();

        $event = [
            'summary' => $task->title,
            'description' => $task->description,
        ];

        if ($task->due_date) {
            $startTime = Carbon::parse($task->due_date);
            if ($task->due_time) {
                $startTime->setTimeFromTimeString($task->due_time);
            }
            $event['start'] = ['dateTime' => $startTime->toRfc3339String()];
            $event['end'] = ['dateTime' => $startTime->addHour()->toRfc3339String()];
        }

        $response = Http::withToken($accessToken)->post(
            "{$config['calendar_url']}/calendars/primary/events",
            $event
        );

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to create calendar event'], 500);
        }

        // Save Google event ID to task
        $task->google_event_id = $response->json('id');
        $task->save();

        return response()->json([
            'message' => 'Task synced to calendar',
            'event' => $response->json(),
        ]);
    }
}
