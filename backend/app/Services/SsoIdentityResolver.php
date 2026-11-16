<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SsoIdentity;

class SsoIdentityResolver
{
    public function findMappedIdentity(string $issuer, string $subject): ?SsoIdentity
    {
        $identity = SsoIdentity::query()
            ->where('identity_hash', SsoIdentity::identityHash($issuer, $subject))
            ->where('active', true)
            ->with([
                'employee.activeAssignment' => fn ($query) => $query->demoOnly(),
                'employee.activeAssignment.orgUnit',
                'employee.activeAssignment.position',
            ])
            ->first();

        $employee = $identity?->employee;
        $assignment = $employee?->activeAssignment;

        if (
            ! $employee
            || ! $employee->demo_only
            || ! $employee->active
            || ! $assignment
            || ! $assignment->demo_only
            || ! ($assignment->orgUnit?->demo_only ?? false)
            || ! ($assignment->position?->demo_only ?? false)
        ) {
            return null;
        }

        return $identity;
    }

    public function findMappedEmployee(string $issuer, string $subject): ?Employee
    {
        return $this->findMappedIdentity($issuer, $subject)?->employee;
    }
}
