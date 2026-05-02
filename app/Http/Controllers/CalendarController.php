<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function events(Request $request): JsonResponse
    {
        $now       = Carbon::now()->toRfc3339String();
        $weekLater = Carbon::now()->addDays(7)->toRfc3339String();

        $items = $this->calendar->listEvents($now, $weekLater);

        return response()->json($items);
    }

    public function syncTask(Request $request): JsonResponse
    {
        $request->validate(['task_id' => 'required|uuid']);

        $task = Task::find($request->input('task_id'));
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if ($task->google_event_id) {
            $ok = $this->calendar->updateEvent($task->google_event_id, $task);
        } else {
            $eventId = $this->calendar->createEvent($task);
            if ($eventId) {
                $task->google_event_id = $eventId;
                $task->saveQuietly();
                $ok = true;
            } else {
                $ok = false;
            }
        }

        return $ok
            ? response()->json(['message' => 'Task synced to calendar'])
            : response()->json(['error' => 'Failed to sync calendar event'], 500);
    }

    public function syncAll(Request $request): JsonResponse
    {
        $tasks = Task::whereNotIn('status', ['cancelled', 'rejected'])->get();

        $synced = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            if ($task->google_event_id) {
                $ok = $this->calendar->updateEvent($task->google_event_id, $task);
            } else {
                $eventId = $this->calendar->createEvent($task);
                if ($eventId) {
                    $task->google_event_id = $eventId;
                    $task->saveQuietly();
                    $ok = true;
                } else {
                    $ok = false;
                }
            }
            $ok ? $synced++ : $failed++;
        }

        return response()->json([
            'message' => "Synced {$synced} tasks" . ($failed ? ", {$failed} failed" : ''),
            'synced'  => $synced,
            'failed'  => $failed,
        ]);
    }

    public function listShares(Request $request): JsonResponse
    {
        $shares = $this->calendar->listCalendarShares();
        return response()->json($shares);
    }

    public function shareCalendar(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'nullable|string|in:reader,writer',
        ]);

        $ok = $this->calendar->addCalendarShare(
            $request->input('email'),
            $request->input('role', 'reader')
        );

        return $ok
            ? response()->json(['message' => 'แชร์ปฏิทินสำเร็จ'])
            : response()->json(['error' => 'ไม่สามารถแชร์ปฏิทินได้'], 500);
    }

    public function unshareCalendar(Request $request): JsonResponse
    {
        $request->validate(['rule_id' => 'required|string']);

        $ok = $this->calendar->removeCalendarShare($request->input('rule_id'));

        return $ok
            ? response()->json(['message' => 'ยกเลิกการแชร์สำเร็จ'])
            : response()->json(['error' => 'ไม่สามารถยกเลิกการแชร์ได้'], 500);
    }
}
