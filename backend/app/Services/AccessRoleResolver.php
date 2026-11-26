<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\EmployeeRoleGrant;

class AccessRoleResolver
{
    public function rolesFor(Employee $employee, ?Assignment $assignment = null): array
    {
        $assignment ??= $employee->activeAssignment()->with('position')->first();
        $primaryRole = $assignment?->position?->access_role;

        if (! $primaryRole) {
            return [];
        }

        $grantQuery = EmployeeRoleGrant::query()
            ->where('employee_id', $employee->id)
            ->where('active', true)
            ->where('demo_only', $employee->demo_only)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()->toDateString()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString()));

        return array_values(array_unique([$primaryRole, ...$grantQuery->orderBy('access_role')->pluck('access_role')->all()]));
    }

    public function has(Employee $employee, string $accessRole, ?Assignment $assignment = null): bool
    {
        return in_array($accessRole, $this->rolesFor($employee, $assignment), true);
    }

    public function canManageKpi(Employee $employee, ?Assignment $assignment = null): bool
    {
        $roles = $this->rolesFor($employee, $assignment);

        return in_array('ADMIN_KPI', $roles, true) || in_array('SDM', $roles, true);
    }

    public function isSelfServiceOnly(Employee $employee, ?Assignment $assignment = null): bool
    {
        if ($this->canManageKpi($employee, $assignment)) {
            return false;
        }

        $assignment ??= $employee->activeAssignment()->with('position')->first();
        $role = $assignment?->position?->access_role;

        return in_array($role, ['PEGAWAI', 'KEPALA_SUB_BIDANG', 'KEPALA_BIDANG'], true);
    }
}
