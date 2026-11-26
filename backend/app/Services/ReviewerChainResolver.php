<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PerformanceContract;

class ReviewerChainResolver
{
    public function resolve(PerformanceContract $contract): array
    {
        $subject = $contract->employee;
        $assignment = $subject?->activeAssignment()->demoOnly()->first();
        $nextEmployeeId = $assignment?->supervisor_employee_id;
        $slots = ['P1' => null, 'P2' => null];
        $seen = $subject ? [$subject->id => true] : [];

        for ($depth = 0; $nextEmployeeId && $depth < 12; $depth++) {
            if (isset($seen[$nextEmployeeId])) {
                break;
            }
            $seen[$nextEmployeeId] = true;

            $reviewer = Employee::query()
                ->where('demo_only', $subject?->demo_only ?? true)
                ->find($nextEmployeeId);
            if (! $reviewer) {
                break;
            }

            $activeAssignment = $reviewer->activeAssignment()->demoOnly()->with('position')->first();
            $latestAssignment = $activeAssignment ?? $reviewer->assignments()
                ->demoOnly()
                ->with('position')
                ->orderByDesc('starts_at')
                ->first();
            $role = $activeAssignment && $reviewer->active ? $activeAssignment->position?->access_role : null;
            if ($role && in_array($role, ['DIREKSI', 'KEPALA_DIVISI', 'KEPALA_BIDANG', 'KEPALA_CABANG', 'KORWIL'], true)) {
                if ($slots['P1'] === null) {
                    $slots['P1'] = $reviewer;
                } elseif ($slots['P2'] === null) {
                    $slots['P2'] = $reviewer;
                }
            }

            $nextEmployeeId = $latestAssignment?->supervisor_employee_id;
            if ($slots['P1'] !== null && $slots['P2'] !== null) {
                break;
            }
        }

        return $slots;
    }
}
