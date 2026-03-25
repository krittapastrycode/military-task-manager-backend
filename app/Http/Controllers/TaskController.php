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

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');
        $allowedSorts = ['title', 'task_type_key', 'status', 'priority', 'deadline_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->query('per_page', 10), 200);
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
            'deadline_at' => 'nullable|date',
            'content' => 'nullable|array',
            'meta' => 'nullable|array',
            'group_id' => 'nullable|uuid',
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
            'content' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $task->update($validated);
        $task->refresh();
        $task->load(['user', 'createdBy']);

        // Real-time sync: update calendar event
        if ($task->google_event_id) {
            $this->calendar->updateEvent($task->google_event_id, $task);
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
            'status' => 'completed',
            'completed' => true,
            'completed_at' => now(),
        ]);
        $task->load(['user', 'createdBy']);

        return response()->json($task);
    }

    private function changeStatus(string $id, string $status): JsonResponse
    {
        $task = Task::findOrFail($id);
        $task->update(['status' => $status]);
        $task->load(['user', 'createdBy']);

        return response()->json($task);
    }
}
