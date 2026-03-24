<?php

namespace App\Http\Controllers;

use App\Models\LineUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        // Verify LINE signature
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();
        $channelSecret = config('services.line.channel_secret');

        $hash = hash_hmac('sha256', $body, $channelSecret, true);
        $expectedSignature = base64_encode($hash);

        if ($signature !== $expectedSignature) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            Log::info('LINE Event', $event);
        }

        return response()->json(['message' => 'OK']);
    }

    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();

        $lineId = $request->query('line_id');
        if (!$lineId) {
            return response()->json(['error' => 'LINE ID required'], 400);
        }

        // Create or update LINE user connection
        $lineUser = LineUser::firstOrNew(['user_id' => $user->id]);
        $lineUser->line_id = $lineId;
        $lineUser->save();

        return response()->json(['message' => 'LINE connected successfully']);
    }

    public function disconnect(Request $request): JsonResponse
    {
        LineUser::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'LINE disconnected']);
    }
}
