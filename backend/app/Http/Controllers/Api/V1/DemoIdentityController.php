<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\PerformanceContract;
use App\Services\DemoAccessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoIdentityController extends Controller
{
    public function index(): JsonResponse
    {
        $this->requireLocalDemo();

        $demoAssignment = static fn ($query) => $query->demoOnly();
        $activeMonthlyCycle = KpiCycle::query()
            ->where('demo_only', true)
            ->where('cadence', 'MONTHLY')
            ->where('status', 'OPEN')
            ->orderByDesc('starts_at')
            ->first();
        $employeesWithContracts = $activeMonthlyCycle
            ? PerformanceContract::query()->demoOnly()->where('cycle_id', $activeMonthlyCycle->id)->pluck('employee_id')->flip()
            : collect();

        $identities = Employee::query()
            ->where('demo_only', true)
            ->where('active', true)
            ->whereIn('id', $this->demoScenarioIdentityIds())
            ->whereHas('activeAssignment', $demoAssignment)
            ->with([
                'activeAssignment' => $demoAssignment,
                'activeAssignment.position',
                'activeAssignment.orgUnit',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Employee $employee) use ($activeMonthlyCycle, $employeesWithContracts): array {
                $assignment = $employee->activeAssignment;
                $accessRole = $assignment->position->access_role;

                return [
                    'id' => $employee->id,
                    'display_name' => $employee->display_name,
                    'access_role' => $accessRole,
                    'org_unit_name' => $assignment->orgUnit->name,
                    'self_assignment_available' => in_array($accessRole, ['PEGAWAI', 'KEPALA_SUB_BIDANG'], true)
                        && $activeMonthlyCycle !== null
                        && ! $employeesWithContracts->has($employee->id),
                ];
            })
            ->values();

        return response()->json(['data' => $identities]);
    }

    public function store(Request $request, DemoAccessContext $context): JsonResponse
    {
        $this->requireLocalDemo();

        $validated = $request->validate([
            'identity_id' => ['required', 'string', 'exists:employees,id'],
        ]);

        $employee = Employee::query()
            ->whereKey($validated['identity_id'])
            ->where('demo_only', true)
            ->where('active', true)
            ->whereIn('id', $this->demoScenarioIdentityIds())
            ->firstOrFail();

        $hasDemoAssignment = $employee->activeAssignment()
            ->demoOnly()
            ->exists();

        abort_unless($hasDemoAssignment, 422, 'Identitas tidak memiliki penugasan demo yang aktif.');

        $request->session()->forget('sso_identity_id');
        Auth::guard('web')->login($employee);
        $request->session()->regenerate();

        return response()->json(['data' => $context->for($employee)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->requireLocalDemo();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([], 204);
    }

    private function requireLocalDemo(): void
    {
        abort_unless(app()->environment('local'), 403, 'Pemilih identitas hanya tersedia pada demo lokal.');
    }

    private function demoScenarioIdentityIds(): array
    {
        return [
            'DEMO-SDM-001',
            'DEMO-DIR-001',
            'DEMO-KADIV-001',
            'DEMO-KABID-001',
            'DEMO-KASUBBID-001',
            'DEMO-STAF-PUSAT-001',
            'DEMO-KORWIL-001',
            'DEMO-KACAB-001',
            'DEMO-PEMASARAN-001',
            'DEMO-OPERASIONAL-001',
        ];
    }
}
