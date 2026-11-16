<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KpiCycle;
use Illuminate\Http\JsonResponse;

class KpiCycleController extends Controller
{
    public function index(): JsonResponse
    {
        $cycles = KpiCycle::query()
            ->where('demo_only', true)
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'cadence', 'formula_version', 'starts_at', 'ends_at', 'status', 'demo_only']);

        return response()->json([
            'data' => $cycles->map(fn (KpiCycle $cycle) => [
                'id' => $cycle->id,
                'name' => $cycle->name,
                'cadence' => $cycle->cadence,
                'formula_version' => $cycle->formula_version,
                'starts_at' => $cycle->starts_at->toDateString(),
                'ends_at' => $cycle->ends_at->toDateString(),
                'status' => $cycle->status,
                'demo_only' => $cycle->demo_only,
            ])->values(),
        ]);
    }
}
