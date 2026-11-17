<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\OrgUnit;
use Illuminate\Support\Collection;

class DemoAccessContext
{
    public function __construct(private readonly AccessRoleResolver $roles)
    {
    }

    public function for(Employee $employee): array
    {
        abort_unless($employee->demo_only, 403, 'Konteks hanya tersedia untuk identitas demo.');

        $assignment = $this->activeDemoAssignment($employee)->with(['orgUnit', 'position'])->first();

        if (! $assignment) {
            abort(403, 'Identitas demo tidak memiliki penugasan demo yang aktif.');
        }

        $role = $assignment->position->access_role;
        $accessRoles = $this->roles->rolesFor($employee, $assignment);

        return [
            'id' => $employee->id,
            'employee_id' => $employee->id,
            'display_name' => $role === 'ADMIN_KPI' ? 'Admin KPI Demo 001' : $employee->display_name,
            'access_role' => $role,
            'access_roles' => $accessRoles,
            'position_name' => $assignment->position->name,
            'org_unit_id' => $assignment->org_unit_id,
            'org_unit_name' => $assignment->orgUnit->name,
            'scope_org_unit_ids' => $this->scopeIds($employee, $assignment),
        ];
    }

    public function scopeIds(Employee $employee, ?Assignment $assignment = null): array
    {
        if (! $employee->demo_only) {
            return [];
        }

        $assignment ??= $this->activeDemoAssignment($employee)->with('position')->first();

        if (! $assignment) {
            return [];
        }

        if (! $assignment->demo_only) {
            return [];
        }

        $role = $assignment->position->access_role;
        if ($this->roles->canManageKpi($employee, $assignment)) {
            return OrgUnit::query()->where('demo_only', true)->orderBy('id')->pluck('id')->all();
        }

        if ($role === 'DIREKSI') {
            $directReportUnitIds = Assignment::query()
                ->active()
                ->demoOnly()
                ->where('supervisor_employee_id', $employee->id)
                ->pluck('org_unit_id');

            return $this->descendantIds($directReportUnitIds);
        }

        if ($role === 'KORWIL') {
            $korwilUnitId = $assignment->korwil_unit_id ?? $assignment->org_unit_id;
            $assignedBranchIds = Assignment::query()
                ->active()
                ->demoOnly()
                ->where('korwil_unit_id', $korwilUnitId)
                ->whereHas('korwilUnit', fn ($query) => $query->where('demo_only', true))
                ->whereHas('orgUnit', fn ($query) => $query->where('type', 'BRANCH'))
                ->pluck('org_unit_id');

            return $this->descendantIds($assignedBranchIds);
        }

        return $this->descendantIds(collect([$assignment->org_unit_id]));
    }

    private function descendantIds(Collection $rootIds): array
    {
        $seen = OrgUnit::query()
            ->where('demo_only', true)
            ->whereIn('id', $rootIds->filter()->unique())
            ->orderBy('id')
            ->pluck('id');
        $frontier = $seen;

        while ($frontier->isNotEmpty()) {
            $children = OrgUnit::query()
                ->where('demo_only', true)
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->diff($seen)
                ->values();

            $seen = $seen->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $seen->all();
    }

    private function activeDemoAssignment(Employee $employee)
    {
        return $employee->activeAssignment()
            ->demoOnly();
    }
}
