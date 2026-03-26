<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class GoogleCalendarService
{
    private string $calendarId;
    private string $calendarUrl = 'https://www.googleapis.com/calendar/v3';
    private string $tokenUrl    = 'https://oauth2.googleapis.com/token';

    public function __construct()
    {
        $this->calendarId = config('services.google.calendar_id', 'primary');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('google_sa_access_token', 3500, function () {
            $jsonPath = storage_path(config('services.google.service_account_json'));

            if (!file_exists($jsonPath)) {
                Log::error('Google service account JSON not found: ' . $jsonPath);
                return null;
            }

            $credentials = json_decode(file_get_contents($jsonPath), true);
            $email       = $credentials['client_email'];
            $privateKey  = $credentials['private_key'];

            $header  = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now     = time();
            $payload = $this->base64UrlEncode(json_encode([
                'iss'   => $email,
                'scope' => 'https://www.googleapis.com/auth/calendar',
                'aud'   => $this->tokenUrl,
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $signingInput = "{$header}.{$payload}";
            $signature    = '';
            openssl_sign($signingInput, $signature, $privateKey, 'sha256WithRSAEncryption');
            $jwt = "{$signingInput}." . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->failed()) {
                Log::error('Failed to get Google service account token', $response->json() ?? []);
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function buildEvent(Task $task): array
    {
        $event = [
            'summary'     => $task->title,
            'description' => $task->description ?? '',
        ];

        // Priority: deadline_at → due_date
        $deadline = $task->deadline_at
            ?? ($task->due_date ? Carbon::parse($task->due_date) : null);

        if ($deadline) {
            $start = Carbon::parse($deadline)->setTimezone('Asia/Bangkok');
            $event['start'] = ['dateTime' => $start->toRfc3339String(), 'timeZone' => 'Asia/Bangkok'];
            $event['end']   = ['dateTime' => $start->copy()->addHour()->toRfc3339String(), 'timeZone' => 'Asia/Bangkok'];
        }

        return $event;
    }

    public function createEvent(Task $task): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->post(
            "{$this->calendarUrl}/calendars/{$calId}/events",
            $this->buildEvent($task)
        );

        if (!$response->successful()) {
            Log::error('Google Calendar createEvent failed', $response->json() ?? []);
            return null;
        }

        return $response->json('id');
    }

    public function updateEvent(string $eventId, Task $task): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->put(
            "{$this->calendarUrl}/calendars/{$calId}/events/{$eventId}",
            $this->buildEvent($task)
        );

        if (!$response->successful()) {
            Log::error('Google Calendar updateEvent failed', $response->json() ?? []);
            return false;
        }

        return true;
    }

    public function deleteEvent(string $eventId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->delete(
            "{$this->calendarUrl}/calendars/{$calId}/events/{$eventId}"
        );

        return $response->successful() || $response->status() === 404;
    }

    public function listEvents(string $timeMin, string $timeMax): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->get("{$this->calendarUrl}/calendars/{$calId}/events", [
            'timeMin'      => $timeMin,
            'timeMax'      => $timeMax,
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
        ]);

        return $response->successful() ? $response->json('items', []) : [];
    }

    public function listCalendarShares(): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->get("{$this->calendarUrl}/calendars/{$calId}/acl");

        if (!$response->successful()) {
            Log::error('Google Calendar listCalendarShares failed', $response->json() ?? []);
            return [];
        }

        return $response->json('items', []);
    }

    public function addCalendarShare(string $email, string $role = 'reader'): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $calId    = urlencode($this->calendarId);
        $response = Http::withToken($token)->post(
            "{$this->calendarUrl}/calendars/{$calId}/acl",
            [
                'role'  => $role,
                'scope' => ['type' => 'user', 'value' => $email],
            ]
        );

        if (!$response->successful()) {
            Log::error('Google Calendar addCalendarShare failed', $response->json() ?? []);
            return false;
        }

        return true;
    }

    public function removeCalendarShare(string $ruleId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $calId      = urlencode($this->calendarId);
        $encodedId  = urlencode($ruleId);
        $response   = Http::withToken($token)->delete(
            "{$this->calendarUrl}/calendars/{$calId}/acl/{$encodedId}"
        );

        return $response->successful() || $response->status() === 404;
    }
}
