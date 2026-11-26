<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\SsoIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SsoProfileProvisioner
{
    public function __construct(private readonly SsoIdentityResolver $identities) {}

    /**
     * Create a local KPI identity from an already verified whoami profile.
     * Privileged KPI admin access is never inferred from free-text profile fields.
     *
     * @param  array<string, mixed>  $profile
     */
    public function provision(string $issuer, array $profile): ?SsoIdentity
    {
        $subject = trim((string) ($profile['employee_id'] ?? ''));
        $displayName = trim((string) ($profile['full_name'] ?? ''));
        $positionName = trim((string) ($profile['job_position'] ?? ''));
        $unitName = trim((string) ($profile['unit_kerja'] ?? ''));
        $branchName = trim((string) ($profile['branch_name'] ?? ''));
        $level = trim((string) ($profile['level'] ?? ''));
        $groupName = trim((string) ($profile['group_jabatan'] ?? ''));

        if (
            $subject === ''
            || ! preg_match('/^[A-Za-z0-9._-]{1,80}$/D', $subject)
            || $displayName === ''
            || $positionName === ''
            || $unitName === ''
            || $branchName === ''
        ) {
            return null;
        }

        $identityHash = SsoIdentity::identityHash($issuer, $subject);
        $identity = DB::transaction(function () use (
            $branchName,
            $displayName,
            $groupName,
            $identityHash,
            $issuer,
            $level,
            $positionName,
            $subject,
            $unitName,
        ): ?SsoIdentity {
            $existingIdentity = SsoIdentity::query()
                ->where('identity_hash', $identityHash)
                ->lockForUpdate()
                ->first();

            if ($existingIdentity) {
                return $existingIdentity->active ? $existingIdentity : null;
            }

            $employee = Employee::query()->whereKey($subject)->lockForUpdate()->first();
            if ($employee && (! $employee->demo_only || ! $employee->active)) {
                return null;
            }

            if (SsoIdentity::query()->where('employee_id', $subject)->where('active', true)->exists()) {
                return null;
            }

            $role = $this->accessRole($positionName, $level);
            $position = $this->position($positionName, $groupName ?: $level ?: 'Pegawai', $role);
            $orgUnit = $this->orgUnit($branchName, $unitName);

            if (! $employee) {
                $employee = Employee::query()->create([
                    'id' => $subject,
                    'display_name' => $displayName,
                    'demo_only' => true,
                    'active' => true,
                ]);
            } else {
                $employee->display_name = $displayName;
                $employee->save();
            }

            $today = now()->toDateString();
            $assignment = $employee->activeAssignment()->demoOnly()->lockForUpdate()->first();
            if ($assignment && $assignment->position_id === $position->id && $assignment->org_unit_id === $orgUnit->id) {
                // Keep any supervisor already supplied by an administrator or a later SIMPEG sync.
            } else {
                if ($assignment) {
                    if ($assignment->starts_at?->toDateString() < $today) {
                        $assignment->ends_at = now()->subDay()->toDateString();
                        $assignment->save();
                        $assignment = null;
                    }
                }

                if ($assignment) {
                    $assignment->position_id = $position->id;
                    $assignment->org_unit_id = $orgUnit->id;
                    $assignment->supervisor_employee_id = null;
                    $assignment->korwil_unit_id = null;
                    $assignment->save();
                } else {
                    Assignment::query()->create([
                        'id' => 'SSO-ASG-'.Str::ulid(),
                        'employee_id' => $employee->id,
                        'position_id' => $position->id,
                        'org_unit_id' => $orgUnit->id,
                        'supervisor_employee_id' => null,
                        'korwil_unit_id' => null,
                        'starts_at' => $today,
                        'ends_at' => null,
                        'is_primary' => true,
                        'demo_only' => true,
                    ]);
                }
            }

            $identity = SsoIdentity::query()->create([
                'id' => 'SSO-'.Str::ulid(),
                'employee_id' => $employee->id,
                'issuer' => $issuer,
                'subject' => $subject,
                'active' => true,
            ]);

            AuditEvent::query()->create([
                'actor_employee_id' => $employee->id,
                'action' => 'SSO_PROFILE_MAPPED',
                'subject_type' => 'SSO_IDENTITY',
                'subject_id' => $subject,
                'after_state' => [
                    'identity_id' => $identity->id,
                    'position_name' => $position->name,
                    'org_unit_name' => $orgUnit->name,
                    'source' => 'whoami',
                ],
            ]);

            return $identity;
        });

        return $identity?->active ? $this->identities->findMappedIdentity($issuer, $subject) : null;
    }

    private function orgUnit(string $branchName, string $unitName): OrgUnit
    {
        $branchCode = $this->stableCode('SSO-BRANCH-', $branchName);
        $branch = OrgUnit::query()->firstOrCreate(
            ['code' => $branchCode],
            [
                'id' => $branchCode,
                'name' => $branchName,
                'type' => 'BRANCH',
                'parent_id' => null,
                'demo_only' => true,
            ],
        );

        if (mb_strtolower($branchName) === mb_strtolower($unitName)) {
            return $branch;
        }

        $unitCode = $this->stableCode('SSO-UNIT-', $branch->id.'|'.$unitName);

        return OrgUnit::query()->firstOrCreate(
            ['code' => $unitCode],
            [
                'id' => $unitCode,
                'name' => $unitName,
                'type' => 'DEPARTMENT',
                'parent_id' => $branch->id,
                'demo_only' => true,
            ],
        );
    }

    private function position(string $name, string $groupName, string $accessRole): Position
    {
        $position = Position::query()
            ->where('name', $name)
            ->where('access_role', $accessRole)
            ->where('demo_only', true)
            ->first();

        if ($position) {
            return $position;
        }

        $id = $this->stableCode('SSO-POS-', $name.'|'.$groupName.'|'.$accessRole);

        return Position::query()->firstOrCreate(
            ['id' => $id],
            [
                'name' => $name,
                'group_name' => $groupName,
                'access_role' => $accessRole,
                'demo_only' => true,
            ],
        );
    }

    private function accessRole(string $positionName, string $level): string
    {
        $title = mb_strtolower($positionName.' '.$level);

        return match (true) {
            str_contains($title, 'kepala kantor wilayah') => 'KORWIL',
            str_contains($title, 'direktur'), str_contains($title, 'direksi') => 'DIREKSI',
            str_contains($title, 'kepala divisi'),
            str_contains($title, 'kepala satuan kerja'),
            str_contains($title, 'sekretaris perusahaan') => 'KEPALA_DIVISI',
            str_contains($title, 'kepala bidang'),
            str_contains($title, 'koordinator satuan kerja') => 'KEPALA_BIDANG',
            str_contains($title, 'kepala sub bidang'), str_contains($title, 'residen') => 'KEPALA_SUB_BIDANG',
            str_contains($title, 'kepala cabang') => 'KEPALA_CABANG',
            default => 'PEGAWAI',
        };
    }

    private function stableCode(string $prefix, string $source): string
    {
        return $prefix.strtoupper(substr(hash('sha256', mb_strtolower(trim($source))), 0, 24));
    }
}
