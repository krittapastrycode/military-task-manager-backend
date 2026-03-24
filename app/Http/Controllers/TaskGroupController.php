<?php

namespace App\Http\Controllers;

use App\Models\TaskGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = TaskGroup::where('user_id', $request->user()->id)
            ->with('tasks')
            ->orderBy('sort_order')
            ->get();

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $group = TaskGroup::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($group, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $group = TaskGroup::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('tasks')
            ->firstOrFail();

        return response()->json($group);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $group = TaskGroup::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $group->update($validated);

        return response()->json($group);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $group = TaskGroup::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }
}
