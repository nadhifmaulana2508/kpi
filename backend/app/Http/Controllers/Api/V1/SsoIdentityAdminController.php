<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SsoIdentity;
use App\Services\AccessRoleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SsoIdentityAdminController extends Controller
{
    private const DEMO_EMPLOYEE_IDS = [
        'DEMO-SDM-001',
        'DEMO-KACAB-001',
        'DEMO-PEMASARAN-001',
        'DEMO-OPERASIONAL-001',
        'DEMO-STAF-PUSAT-001',
    ];

    public function index(Request $request, AccessRoleResolver $roles): JsonResponse
    {
        $this->admin($request, $roles);
        $issuer = $this->issuer();

        $mappings = SsoIdentity::query()
            ->where('issuer', $issuer)
            ->with([
                'employee.activeAssignment' => fn ($query) => $query->demoOnly(),
                'employee.activeAssignment.position',
                'employee.activeAssignment.orgUnit',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (SsoIdentity $identity): array => $this->mappingData($identity, $roles))
            ->values();

        $demoAssignment = static fn ($query) => $query->demoOnly();
        $candidates = Employee::query()
            ->whereIn('id', self::DEMO_EMPLOYEE_IDS)
            ->where('demo_only', true)
            ->where('active', true)
            ->whereHas('activeAssignment', $demoAssignment)
            ->with([
                'activeAssignment' => $demoAssignment,
                'activeAssignment.position',
                'activeAssignment.orgUnit',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Employee $employee) use ($roles): array {
                $assignment = $employee->activeAssignment;
                $accessRoles = $roles->rolesFor($employee, $assignment);

                return [
                    'employee_id' => $employee->id,
                    'display_name' => $assignment->position->access_role === 'ADMIN_KPI'
                        ? 'Admin KPI Demo 001'
                        : $employee->display_name,
                    'position_name' => $assignment->position->name,
                    'access_role' => $assignment->position->access_role,
                    'access_roles' => $accessRoles,
                    'org_unit_name' => $assignment->orgUnit->name,
                ];
            })
            ->values();

        return response()->json(['data' => [
            'issuer' => $issuer,
            'mappings' => $mappings,
            'candidates' => $candidates,
        ]]);
    }

    public function store(Request $request, AccessRoleResolver $roles): JsonResponse
    {
        $this->admin($request, $roles);

        $validated = $request->validate([
            'external_employee_id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/D'],
            'employee_id' => ['required', 'string', 'in:'.implode(',', self::DEMO_EMPLOYEE_IDS)],
        ]);

        $subject = trim($validated['external_employee_id']);
        $employee = $this->demoCandidate($validated['employee_id']);
        $issuer = $this->issuer();
        $identityHash = SsoIdentity::identityHash($issuer, $subject);

        $identity = DB::transaction(function () use ($employee, $identityHash, $issuer, $subject): SsoIdentity {
            $existing = SsoIdentity::query()
                ->where('identity_hash', $identityHash)
                ->lockForUpdate()
                ->first();

            if ($existing?->active) {
                throw ValidationException::withMessages([
                    'external_employee_id' => 'ID pegawai SSO ini sudah memiliki mapping aktif.',
                ]);
            }

            $employeeAlreadyMapped = SsoIdentity::query()
                ->where('employee_id', $employee->id)
                ->where('active', true)
                ->when($existing, fn ($query) => $query->where('id', '<>', $existing->getKey()))
                ->lockForUpdate()
                ->exists();

            if ($employeeAlreadyMapped) {
                throw ValidationException::withMessages([
                    'employee_id' => 'User KPI demo ini sudah dipasangkan dengan ID SSO lain.',
                ]);
            }

            if ($existing) {
                $existing->employee_id = $employee->id;
                $existing->active = true;
                $existing->save();

                return $existing->fresh();
            }

            return SsoIdentity::query()->create([
                'id' => 'SSO-'.Str::ulid(),
                'employee_id' => $employee->id,
                'issuer' => $issuer,
                'subject' => $subject,
                'active' => true,
            ]);
        });

        $identity->load([
            'employee.activeAssignment' => fn ($query) => $query->demoOnly(),
            'employee.activeAssignment.position',
            'employee.activeAssignment.orgUnit',
        ]);

        return response()->json(['data' => $this->mappingData($identity, $roles)], 201);
    }

    public function update(Request $request, string $identityId, AccessRoleResolver $roles): JsonResponse
    {
        $this->admin($request, $roles);

        $validated = $request->validate(['active' => ['required', 'boolean']]);
        $identity = SsoIdentity::query()
            ->where('issuer', $this->issuer())
            ->whereKey($identityId)
            ->firstOrFail();

        if ($validated['active']) {
            $employeeAlreadyMapped = SsoIdentity::query()
                ->where('employee_id', $identity->employee_id)
                ->where('active', true)
                ->where('id', '<>', $identity->getKey())
                ->exists();

            if ($employeeAlreadyMapped) {
                throw ValidationException::withMessages([
                    'active' => 'User KPI demo ini sudah dipasangkan dengan ID SSO lain.',
                ]);
            }
        }

        $identity->active = (bool) $validated['active'];
        $identity->save();
        $identity->load([
            'employee.activeAssignment' => fn ($query) => $query->demoOnly(),
            'employee.activeAssignment.position',
            'employee.activeAssignment.orgUnit',
        ]);

        return response()->json(['data' => $this->mappingData($identity, $roles)]);
    }

    private function admin(Request $request, AccessRoleResolver $roles): Employee
    {
        $employee = $request->user();
        abort_unless($employee instanceof Employee && $employee->demo_only && $employee->active, 403);

        $assignment = $employee->activeAssignment()
            ->demoOnly()
            ->with('position')
            ->first();

        abort_unless($assignment && $roles->canManageKpi($employee, $assignment), 403, 'Fitur ini hanya tersedia untuk Admin KPI/SDM.');

        return $employee;
    }

    private function demoCandidate(string $employeeId): Employee
    {
        $employee = Employee::query()
            ->whereKey($employeeId)
            ->whereIn('id', self::DEMO_EMPLOYEE_IDS)
            ->where('demo_only', true)
            ->where('active', true)
            ->whereHas('activeAssignment', fn ($query) => $query->demoOnly())
            ->first();

        abort_unless($employee, 422, 'Pilih user KPI demo yang aktif dan memiliki penugasan demo.');

        return $employee;
    }

    private function issuer(): string
    {
        return (string) (config('services.sso.issuer') ?: rtrim((string) config('services.sso.base_url'), '/'));
    }

    private function mappingData(SsoIdentity $identity, AccessRoleResolver $roles): array
    {
        $employee = $identity->employee;
        $assignment = $employee?->activeAssignment;

        return [
            'id' => $identity->id,
            'external_employee_id' => $identity->subject,
            'employee_id' => $identity->employee_id,
            'employee_name' => $assignment?->position?->access_role === 'ADMIN_KPI'
                ? 'Admin KPI Demo 001'
                : $employee?->display_name,
            'position_name' => $assignment?->position?->name,
            'access_role' => $assignment?->position?->access_role,
            'access_roles' => $employee ? $roles->rolesFor($employee, $assignment) : [],
            'org_unit_name' => $assignment?->orgUnit?->name,
            'active' => $identity->active,
            'updated_at' => $identity->updated_at?->toISOString(),
        ];
    }
}
