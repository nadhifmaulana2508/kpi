<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\PerformanceContract;
use App\Services\AccessRoleResolver;
use App\Services\DemoAccessContext;
use App\Services\DemoScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DemoAccessContext $context, DemoScoreCalculator $scores, AccessRoleResolver $roles): JsonResponse
    {
        $cycleId = $request->query('cycle_id');
        $cycle = $cycleId
            ? KpiCycle::query()->where('demo_only', true)->findOrFail($cycleId)
            : KpiCycle::query()->where('demo_only', true)->where('status', 'OPEN')->orderByDesc('starts_at')->orderByDesc('created_at')->firstOrFail();

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        $role = $assignment->position->access_role;
        $scopeIds = $context->scopeIds($employee, $assignment);
        $contracts = PerformanceContract::query()
            ->demoOnly()
            ->where('cycle_id', $cycle->id)
            ->with('items');

        $selfServiceOnly = $roles->isSelfServiceOnly($employee, $assignment);

        if ($selfServiceOnly) {
            $contracts->where(fn ($query) => $query
                ->where('employee_id', $employee->id)
                ->orWhere('reviewer_employee_id', $employee->id));
        } else {
            $contracts->whereIn('org_unit_id', $scopeIds);
        }

        $scopedContracts = $contracts->get();

        $actionQuery = PerformanceContract::query()
            ->demoOnly()
            ->where('cycle_id', $cycle->id);

        if ($roles->has($employee, 'SDM', $assignment)) {
            $actionQuery->where('status', 'VERIFIED');
        } else {
            $actionQuery->where('reviewer_employee_id', $employee->id)
                ->where('status', 'SUBMITTED');
        }

        return response()->json([
            'data' => [
                'cycle' => [
                    'id' => $cycle->id,
                    'name' => $cycle->name,
                    'cadence' => $cycle->cadence,
                    'formula_version' => $cycle->formula_version,
                    'starts_at' => $cycle->starts_at->toDateString(),
                    'ends_at' => $cycle->ends_at->toDateString(),
                    'status' => $cycle->status,
                    'demo_only' => $cycle->demo_only,
                ],
                'counts' => [
                    'total_contracts' => $scopedContracts->count(),
                    'ready_contracts' => $scopedContracts
                        ->filter(fn (PerformanceContract $contract) => $scores->calculate($contract)['kpi_score'] !== null)
                        ->count(),
                    'submitted_contracts' => $scopedContracts->where('status', 'SUBMITTED')->count(),
                ],
                'average_final_score' => null,
                'contracts_needing_action' => $actionQuery->count(),
            ],
        ]);
    }
}
