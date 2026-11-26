<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Services\AccessRoleResolver;
use App\Services\PositionKpiVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KpiCycleController extends Controller
{
    public function index(PositionKpiVersionService $versions): JsonResponse
    {
        $cycles = KpiCycle::query()
            ->where('demo_only', true)
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'cadence', 'formula_version', 'starts_at', 'ends_at', 'status', 'demo_only']);

        $cycles
            ->where('status', 'OPEN')
            ->each(fn (KpiCycle $cycle) => $versions->snapshotOpenCycle($cycle));

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

    public function store(Request $request, AccessRoleResolver $roles, PositionKpiVersionService $versions): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        abort_unless($roles->canManageKpi($employee), 403, 'Hanya Admin KPI yang dapat mengatur periode.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cadence_months' => ['required', 'integer', 'in:1,3,6,12'],
            'starts_at' => ['required', 'date'],
            'status' => ['required', 'in:DRAFT,OPEN,CLOSED'],
        ]);

        $months = (int) $validated['cadence_months'];
        $cadence = match ($months) {
            1 => 'MONTHLY',
            3 => 'QUARTERLY',
            6 => 'SEMIANNUAL',
            12 => 'ANNUAL',
        };
        $startsAt = Carbon::parse($validated['starts_at'])->startOfDay();
        $endsAt = $startsAt->copy()->addMonthsNoOverflow($months)->subDay();
        abort_if(
            KpiCycle::query()
                ->where('demo_only', true)
                ->where('cadence', $cadence)
                ->whereDate('starts_at', $startsAt->toDateString())
                ->exists(),
            422,
            'Periode dengan durasi dan tanggal mulai tersebut sudah ada. Ubah status dari daftar periode.'
        );

        $cycle = KpiCycle::query()->create([
            'id' => 'DEMO-CYCLE-'.Str::uuid(),
            'name' => $validated['name'],
            'cadence' => $cadence,
            'formula_version' => 'demo-prototype-v2',
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'status' => $validated['status'],
            'demo_only' => true,
        ]);

        $versions->snapshotOpenCycle($cycle);

        return response()->json(['data' => [
            'id' => $cycle->id,
            'name' => $cycle->name,
            'cadence' => $cycle->cadence,
            'formula_version' => $cycle->formula_version,
            'starts_at' => $cycle->starts_at->toDateString(),
            'ends_at' => $cycle->ends_at->toDateString(),
            'status' => $cycle->status,
            'demo_only' => $cycle->demo_only,
        ]], 201);
    }

    public function update(Request $request, string $cycleId, AccessRoleResolver $roles, PositionKpiVersionService $versions): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        abort_unless($roles->canManageKpi($employee), 403, 'Hanya Admin KPI yang dapat mengatur periode.');

        $cycle = KpiCycle::query()
            ->where('demo_only', true)
            ->findOrFail($cycleId);
        $validated = $request->validate([
            'status' => ['required', 'in:DRAFT,OPEN,CLOSED'],
        ]);

        if ($validated['status'] === 'OPEN') {
            abort_if(
                KpiCycle::query()
                    ->where('demo_only', true)
                    ->where('id', '!=', $cycle->id)
                    ->where('cadence', $cycle->cadence)
                    ->whereDate('starts_at', $cycle->starts_at->toDateString())
                    ->where('status', 'OPEN')
                    ->exists(),
                422,
                'Periode dengan durasi dan tanggal mulai yang sama sudah terbuka.'
            );
        }

        $cycle->update(['status' => $validated['status']]);

        if ($cycle->status === 'OPEN') {
            $versions->snapshotOpenCycle($cycle);
        }

        return response()->json(['data' => [
            'id' => $cycle->id,
            'name' => $cycle->name,
            'cadence' => $cycle->cadence,
            'formula_version' => $cycle->formula_version,
            'starts_at' => $cycle->starts_at->toDateString(),
            'ends_at' => $cycle->ends_at->toDateString(),
            'status' => $cycle->status,
            'demo_only' => $cycle->demo_only,
        ]]);
    }
}
