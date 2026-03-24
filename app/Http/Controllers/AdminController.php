<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * GET /api/admin
     * Paginated list of users/personnel with search.
     */
    public function get(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%')
                  ->orWhere('rank', 'ilike', '%' . $search . '%')
                  ->orWhere('position', 'ilike', '%' . $search . '%');
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $query->orderBy('name');

        $perPage = min((int) $request->query('per_page', 10), 100);
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
     * GET /api/admin/{id}
     */
    public function find(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }
}
