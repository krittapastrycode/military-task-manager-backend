<?php

namespace App\Http\Controllers;

use App\Models\ProtectionPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtectionPersonController extends Controller
{
    /** GET /api/protection-persons?category=royal|vip */
    public function index(Request $request): JsonResponse
    {
        $query = ProtectionPerson::active()->orderBy('order');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $persons = $query->get(['id', 'category', 'name', 'order']);

        return response()->json([
            'data' => $persons,
            'royal' => $persons->where('category', 'royal')->values(),
            'vip'   => $persons->where('category', 'vip')->values(),
        ]);
    }
}
