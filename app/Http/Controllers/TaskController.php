<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}
    /**
     * GET /api/task
     * Paginated task list with sorting, search, and filters.
     */
    public function get(Request $request): JsonResponse
    {
        $query = Task::with(['user', 'createdBy']);

        // Search by title
        if ($search = $request->query('search')) {
            $query->where('title', 'ilike', '%' . $search . '%');
        }

        // Filter by task_type_key
        if ($typeKey = $request->query('task_type_key')) {
            $query->where('task_type_key', $typeKey);
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by priority
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        // Filter by date range (deadline_at)
        if ($dateFrom = $request->query('date_from')) {
            $query->where('deadline_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo = $request->query('date_to')) {
            $query->where('deadline_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        // Filter by completed_at range (used by PDF export)
        if ($completedFrom = $request->query('completed_from')) {
            $query->where('completed_at', '>=', Carbon::parse($completedFrom)->startOfDay());
        }
        if ($completedTo = $request->query('completed_to')) {
            $query->where('completed_at', '<=', Carbon::parse($completedTo)->endOfDay());
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');
        $allowedSorts = ['title', 'task_type_key', 'status', 'priority', 'deadline_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination (allow up to 500 for export queries that use completed_from/completed_to)
        $maxPerPage = $request->query('completed_from') || $request->query('completed_to') ? 500 : 200;
        $perPage = min((int) $request->query('per_page', 10), $maxPerPage);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/task/today
     * Get tasks due within a date range from today.
     */
    public function getToday(Request $request): JsonResponse
    {
        $daysFrom = (int) $request->query('days_from', 0);
        $days = (int) $request->query('days', 1);

        $startDate = Carbon::today()->addDays($daysFrom)->startOfDay();
        $endDate = Carbon::today()->addDays($daysFrom + $days - 1)->endOfDay();

        $tasks = Task::with(['user', 'createdBy'])
            ->where('deadline_at', '>=', $startDate)
            ->where('deadline_at', '<=', $endDate)
            ->orderBy('deadline_at')
            ->get();

        return response()->json([
            'data' => $tasks,
        ]);
    }

    /**
     * POST /api/task
     * Create a new task.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type_key' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'deadline_at' => 'nullable|date|after_or_equal:now',
            'end_at' => 'nullable|date|after_or_equal:deadline_at',
            'content' => 'nullable|array',
            'meta' => 'nullable|array',
            'group_id' => 'nullable|uuid',
        ], [
            'deadline_at.after_or_equal' => 'ไม่สามารถสร้างภารกิจในอดีตได้',
            'end_at.after_or_equal' => 'เวลาเสร็จงานต้องไม่น้อยกว่าวันปฏิบัติภารกิจ',
        ]);

        $task = Task::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'created_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        $task->load(['user', 'createdBy']);

        // Auto-sync to Google Calendar (service account)
        $eventId = $this->calendar->createEvent($task);
        if ($eventId) {
            $task->google_event_id = $eventId;
            $task->saveQuietly();
        }

        return response()->json($task, 201);
    }

    /**
     * GET /api/task/{id}
     * Get a single task.
     */
    public function find(Request $request, string $id): JsonResponse
    {
        $task = Task::with(['user', 'createdBy', 'group'])
            ->findOrFail($id);

        return response()->json($task);
    }

    /**
     * PATCH /api/task/{id}
     * Update a task.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'task_type_key' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'status' => 'nullable|string',
            'deadline_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'content' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $task->update($validated);
        $task->refresh();
        $task->load(['user', 'createdBy']);

        // Real-time sync: update calendar event
        if ($task->google_event_id) {
            $updated = $this->calendar->updateEvent($task->google_event_id, $task);
            if (!$updated) {
                // Event may have been deleted from Calendar manually — recreate it
                $eventId = $this->calendar->createEvent($task);
                if ($eventId) {
                    $task->google_event_id = $eventId;
                    $task->saveQuietly();
                }
            }
        } else {
            $eventId = $this->calendar->createEvent($task);
            if ($eventId) {
                $task->google_event_id = $eventId;
                $task->saveQuietly();
            }
        }

        return response()->json($task);
    }

    /**
     * DELETE /api/task/{id}
     * Delete a task.
     */
    public function delete(Request $request, string $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        // Real-time sync: delete calendar event before removing task
        if ($task->google_event_id) {
            $this->calendar->deleteEvent($task->google_event_id);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    /**
     * PATCH /api/task/approve/{id}
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        return $this->changeStatus($id, 'approved');
    }

    /**
     * PATCH /api/task/reject/{id}
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        return $this->changeStatus($id, 'rejected');
    }

    /**
     * PATCH /api/task/cancel/{id}
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        return $this->changeStatus($id, 'cancelled');
    }

    /**
     * PATCH /api/task/on-hold/{id}
     */
    public function onHold(Request $request, string $id): JsonResponse
    {
        return $this->changeStatus($id, 'on_hold');
    }

    /**
     * PATCH /api/task/complete/{id}
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $task->update([
            'status' => 'success',
            'completed' => true,
            'completed_at' => now(),
        ]);
        $task->load(['user', 'createdBy']);

        return response()->json($task);
    }

    /**
     * GET /api/report/chart
     * Completed-task counts per task type, grouped by month (year view)
     * or by week (quarter view).
     */
    public function chart(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $quarterParam = $request->query('quarter');
        $quarter = ($quarterParam !== null && $quarterParam !== '')
            ? (int) $quarterParam
            : null;

        $types = ['royal_security', 'vip_protection', 'convoy', 'traffic', 'venue_security'];

        $emptyCounts = fn () => array_fill_keys($types, 0);

        if ($quarter === null) {
            // ── Year view: 12 months ──
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();

            $thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

            $data = [];
            for ($m = 1; $m <= 12; $m++) {
                $data[$m] = [
                    'period' => sprintf('%04d-%02d', $year, $m),
                    'label' => $thaiMonths[$m - 1],
                    ...$emptyCounts(),
                ];
            }

            $tasks = Task::whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->get(['task_type_key', 'completed_at']);

            foreach ($tasks as $task) {
                $key = (int) Carbon::parse($task->completed_at)->month;
                $type = $task->task_type_key;
                if (isset($data[$key]) && in_array($type, $types, true)) {
                    $data[$key][$type]++;
                }
            }

            return response()->json(['data' => array_values($data)]);
        }

        // ── Quarter view: weeks within the quarter ──
        $quarter = max(1, min(4, $quarter));
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();

        // Build week buckets: each week starts on Monday.
        $buckets = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weekIndex = 1;
        while ($cursor <= $end) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
            $buckets[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
                'period' => $weekStart->format('Y-m-d'),
                'label' => 'สป.' . $weekIndex,
                'counts' => $emptyCounts(),
            ];
            $cursor->addWeek();
            $weekIndex++;
        }

        $tasks = Task::whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['task_type_key', 'completed_at']);

        foreach ($tasks as $task) {
            $completedAt = Carbon::parse($task->completed_at);
            $type = $task->task_type_key;
            if (!in_array($type, $types, true)) {
                continue;
            }
            foreach ($buckets as &$bucket) {
                if ($completedAt->betweenIncluded($bucket['start'], $bucket['end'])) {
                    $bucket['counts'][$type]++;
                    break;
                }
            }
            unset($bucket);
        }

        $data = array_map(fn ($b) => [
            'period' => $b['period'],
            'label' => $b['label'],
            ...$b['counts'],
        ], $buckets);

        return response()->json(['data' => $data]);
    }

    private function changeStatus(string $id, string $status): JsonResponse
    {
        $task = Task::findOrFail($id);
        $task->update(['status' => $status]);
        $task->load(['user', 'createdBy']);

        return response()->json($task);
    }
}
