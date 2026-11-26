<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\PerformanceContract;
use App\Services\AccessRoleResolver;
use App\Services\DemoAccessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoIdentityController extends Controller
{
    public function index(AccessRoleResolver $roles): JsonResponse
    {
        $this->requireLocalDemo();

        $demoAssignment = static fn ($query) => $query->demoOnly();
        $activeCycle = KpiCycle::query()
            ->where('demo_only', true)
            ->where('status', 'OPEN')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->first();
        $employeesWithContracts = $activeCycle
            ? PerformanceContract::query()->demoOnly()->where('cycle_id', $activeCycle->id)->pluck('employee_id')->flip()
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
            ->map(function (Employee $employee) use ($activeCycle, $employeesWithContracts, $roles): array {
                $assignment = $employee->activeAssignment;
                $accessRole = $assignment->position->access_role;
                $accessRoles = $roles->rolesFor($employee, $assignment);

                return [
                    'id' => $employee->id,
                    'display_name' => $accessRole === 'ADMIN_KPI' ? 'Admin KPI' : $employee->display_name,
                    'access_role' => $accessRole,
                    'access_roles' => $accessRoles,
                    'org_unit_name' => $assignment->orgUnit->name,
                    'self_assignment_available' => in_array($accessRole, ['PEGAWAI', 'KEPALA_SUB_BIDANG', 'KEPALA_BIDANG'], true)
                        && $activeCycle !== null
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

        abort_unless($hasDemoAssignment, 422, 'Identitas tidak memiliki penugasan aktif.');

        $request->session()->forget('sso_identity_id');
        Auth::guard('web')->login($employee);
        $request->session()->regenerate();

        return response()->json(['data' => $context->for($employee) + ['auth_source' => 'DEMO']]);
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
        abort_unless(app()->environment('local'), 403, 'Pemilih profil hanya tersedia pada lingkungan lokal.');
    }

    private function demoScenarioIdentityIds(): array
    {
        return [
            'DEMO-SDM-001',
            'DEMO-KASUBBID-001',
            'DEMO-KABID-OPS-001',
            'DEMO-KABID-001',
            'DEMO-KACAB-001',
            'DEMO-AO-KREDIT-001',
            'DEMO-OPERASIONAL-001',
        ];
    }
}
