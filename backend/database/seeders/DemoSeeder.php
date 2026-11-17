<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\AuditEvent;
use App\Models\ContractComment;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\KpiDefinition;
use App\Models\OrgUnit;
use App\Models\PerformanceContract;
use App\Models\Position;
use App\Models\ReviewEvent;
use App\Services\DemoScoreCalculator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    private array $positions = [];

    public function run(): void
    {
        $this->assertDemoEnvironment();

        DB::transaction(function (): void {
            $this->clearDemoData();
            $units = $this->createOrganization();
            $this->createPositions();
            $profiles = $this->createPeople($units);
            $this->assertDemoStructure($units, $profiles);
            $definitions = $this->createDefinitions();
            $this->call(DemoRoleGrantSeeder::class);
            $cycle = $this->createCycle();
            $this->createContracts($profiles, $definitions, $cycle, app(DemoScoreCalculator::class));
            $this->assertDemoSeedRows();
        });

        $this->command?->info('Data demo dibuat: 116 unit, 11 jabatan, 128 identitas, 1 role tambahan Admin KPI, 60 definisi KPI, 120 kontrak, dan empat identitas khusus self-assignment.');
    }

    private function assertDemoEnvironment(): void
    {
        $local = app()->environment('local');
        $isolatedUat = app()->environment('staging') && (bool) config('demo.data_allowed');

        if (! $local && ! $isolatedUat) {
            throw new RuntimeException('Seed demo hanya boleh dijalankan pada local atau UAT terisolasi dengan DEMO_DATA_ALLOWED=true.');
        }
    }

    private function assertDemoStructure(array $units, array $profiles): void
    {
        $expectedUnitCounts = [
            'BRANCH' => 28,
            'DEPARTMENT' => 9,
            'DIVISION' => 9,
            'HEAD_OFFICE' => 1,
            'REGION' => 4,
            'SECTION' => 56,
            'SUB_DEPARTMENT' => 9,
        ];
        $actualUnitCounts = OrgUnit::query()
            ->where('demo_only', true)
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn ($count): int => (int) $count)
            ->all();
        ksort($expectedUnitCounts);
        ksort($actualUnitCounts);

        if ($actualUnitCounts !== $expectedUnitCounts) {
            throw new RuntimeException('Struktur unit demo tidak sesuai jumlah yang ditetapkan.');
        }

        $expectedParents = [$units['hq'] => null];

        foreach ($units['divisions'] as $number => $divisionId) {
            $expectedParents[$divisionId] = $units['hq'];
            $expectedParents[$units['departments'][$number]] = $divisionId;
            $expectedParents[$units['subDepartments'][$number]] = $units['departments'][$number];
        }

        foreach ($units['regions'] as $regionId) {
            $expectedParents[$regionId] = $units['hq'];
        }

        foreach ($units['branches'] as $number => $branchId) {
            $regionNumber = intdiv($number - 1, 7) + 1;
            $expectedParents[$branchId] = $units['regions'][$regionNumber];
            $expectedParents[$units['sections'][$number]['marketing']] = $branchId;
            $expectedParents[$units['sections'][$number]['operations']] = $branchId;
        }

        $actualParents = OrgUnit::query()
            ->whereIn('id', array_keys($expectedParents))
            ->pluck('parent_id', 'id')
            ->all();

        ksort($expectedParents);
        ksort($actualParents);

        if ($actualParents !== $expectedParents) {
            throw new RuntimeException('Relasi induk unit demo tidak sesuai struktur organisasi yang ditetapkan.');
        }

        if (count($profiles) !== 128) {
            throw new RuntimeException('Jumlah identitas demo tidak sesuai rencana seed.');
        }

        $expectedPositionCounts = [
            'ADMIN-KPI' => 1,
            'DIR' => 3,
            'KADIV' => 9,
            'KABID' => 9,
            'KASUBBID' => 9,
            'STAF-PUSAT' => 8,
            'STAF-PUSAT-OPS' => 1,
            'KORWIL' => 4,
            'KACAB' => 28,
            'PEMASARAN' => 28,
            'OPERASIONAL' => 28,
        ];
        $actualPositionCounts = collect($profiles)->countBy('position_code');

        foreach ($expectedPositionCounts as $positionCode => $expectedCount) {
            if ($actualPositionCounts->get($positionCode, 0) !== $expectedCount) {
                throw new RuntimeException("Jumlah pegawai demo untuk posisi {$positionCode} tidak sesuai.");
            }
        }

        $expectedSupervisors = ['DEMO-SDM-001' => null];
        $directorIds = [];

        for ($number = 1; $number <= 3; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $directorId = "DEMO-DIR-$suffix";
            $directorIds[] = $directorId;
            $expectedSupervisors[$directorId] = null;
        }

        for ($number = 1; $number <= 9; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $directorSuffix = str_pad((string) (intdiv($number - 1, 3) + 1), 3, '0', STR_PAD_LEFT);
            $divisionHeadId = "DEMO-KADIV-$suffix";
            $departmentHeadId = "DEMO-KABID-$suffix";
            $subDepartmentHeadId = "DEMO-KASUBBID-$suffix";

            $expectedSupervisors[$divisionHeadId] = "DEMO-DIR-$directorSuffix";
            $expectedSupervisors[$departmentHeadId] = $divisionHeadId;
            $expectedSupervisors[$subDepartmentHeadId] = $departmentHeadId;
            $expectedSupervisors["DEMO-STAF-PUSAT-$suffix"] = $number === 1 ? $divisionHeadId : $subDepartmentHeadId;
        }

        foreach ($expectedSupervisors as $employeeId => $expectedSupervisorId) {
            $employeeProfile = $profiles[$employeeId] ?? null;

            if (! is_array($employeeProfile)
                || ! array_key_exists('supervisor_id', $employeeProfile)
                || $employeeProfile['supervisor_id'] !== $expectedSupervisorId) {
                throw new RuntimeException("Atasan langsung pegawai demo {$employeeId} tidak sesuai struktur.");
            }
        }

        for ($number = 1; $number <= 4; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $korwilId = "DEMO-KORWIL-$suffix";
            $supervisorId = $profiles[$korwilId]['supervisor_id'] ?? null;

            if (! in_array($supervisorId, $directorIds, true)) {
                throw new RuntimeException("Korwil demo {$korwilId} harus melapor kepada salah satu Direksi demo.");
            }
        }

        $expectedBranchSupervisors = [];

        for ($number = 1; $number <= 28; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $regionNumber = intdiv($number - 1, 7) + 1;
            $regionSuffix = str_pad((string) $regionNumber, 3, '0', STR_PAD_LEFT);
            $branchHeadId = "DEMO-KACAB-$suffix";
            $expectedBranchSupervisors[$branchHeadId] = "DEMO-KORWIL-$regionSuffix";
            $expectedBranchSupervisors["DEMO-PEMASARAN-$suffix"] = $branchHeadId;
            $expectedBranchSupervisors["DEMO-OPERASIONAL-$suffix"] = $branchHeadId;
        }

        foreach ($expectedBranchSupervisors as $employeeId => $expectedSupervisorId) {
            $actualSupervisorId = $profiles[$employeeId]['supervisor_id'] ?? 'MISSING';

            if ($actualSupervisorId !== $expectedSupervisorId) {
                throw new RuntimeException("Atasan langsung pegawai demo {$employeeId} tidak sesuai struktur.");
            }
        }

        $sectionsByBranch = OrgUnit::query()
            ->where('demo_only', true)
            ->where('type', 'SECTION')
            ->get(['parent_id', 'name'])
            ->groupBy('parent_id');

        foreach ($units['branches'] as $number => $branchId) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $expectedSectionNames = ["Operasional Demo {$suffix}", "Pemasaran Demo {$suffix}"];
            sort($expectedSectionNames);
            $actualSectionNames = $sectionsByBranch->get($branchId, collect())
                ->pluck('name')
                ->sort()
                ->values()
                ->all();

            if ($actualSectionNames !== $expectedSectionNames) {
                throw new RuntimeException("Cabang {$suffix} wajib memiliki bagian Pemasaran dan Operasional.");
            }
        }

        $actualBranchesPerKorwil = Assignment::query()
            ->where('demo_only', true)
            ->where('position_id', $this->positions['KACAB'])
            ->selectRaw('korwil_unit_id, count(*) as aggregate')
            ->groupBy('korwil_unit_id')
            ->pluck('aggregate', 'korwil_unit_id')
            ->all();
        $expectedBranchesPerKorwil = array_fill_keys(array_values($units['regions']), 7);
        $normalizeCounts = static function (array $counts): array {
            $normalized = [];
            foreach ($counts as $key => $count) {
                $normalized[(string) $key] = (int) $count;
            }
            ksort($normalized);

            return $normalized;
        };

        if ($normalizeCounts($actualBranchesPerKorwil) !== $normalizeCounts($expectedBranchesPerKorwil)) {
            throw new RuntimeException('Setiap Korwil demo harus memiliki tujuh cabang yang ditugaskan.');
        }
    }

    private function assertDemoSeedRows(): void
    {
        $expectedCounts = [
            'org_units' => 116,
            'positions' => 11,
            'employees' => 128,
            'assignments' => 128,
            'employee_role_grants' => 1,
            'kpi_definitions' => 60,
            'cycles' => 1,
            'performance_contracts' => 120,
        ];

        foreach ($expectedCounts as $table => $expectedCount) {
            $demoRows = DB::table($table)->where('id', 'like', 'DEMO-%');
            $actualCount = (clone $demoRows)->count();
            $containsNonDemoRows = (clone $demoRows)->where('demo_only', false)->exists();

            if ($actualCount !== $expectedCount || $containsNonDemoRows) {
                throw new RuntimeException("Isi seed {$table} harus berjumlah {$expectedCount} dan seluruhnya demo_only.");
            }
        }

        $expectedAssignmentsByPosition = [
            'DEMO-POS-DIR' => 3,
            'DEMO-POS-KADIV' => 9,
            'DEMO-POS-KABID' => 9,
            'DEMO-POS-KASUBBID' => 9,
            'DEMO-POS-STAF-PUSAT' => 8,
            'DEMO-POS-STAF-PUSAT-OPS' => 1,
            'DEMO-POS-KORWIL' => 4,
            'DEMO-POS-KACAB' => 28,
            'DEMO-POS-PEMASARAN' => 28,
            'DEMO-POS-OPERASIONAL' => 28,
            'DEMO-POS-ADMIN-KPI' => 1,
        ];

        $actualAssignmentsByPosition = DB::table('assignments')
            ->where('demo_only', true)
            ->select('position_id')
            ->selectRaw('COUNT(*) as assignment_count')
            ->groupBy('position_id')
            ->pluck('assignment_count', 'position_id')
            ->map(fn ($count): int => (int) $count)
            ->all();

        ksort($expectedAssignmentsByPosition);
        ksort($actualAssignmentsByPosition);

        if ($actualAssignmentsByPosition !== $expectedAssignmentsByPosition) {
            throw new RuntimeException('Distribusi penugasan demo tidak sesuai struktur organisasi yang ditetapkan.');
        }

        $validDemoContracts = PerformanceContract::query()
            ->where('id', 'like', 'DEMO-%')
            ->demoOnly()
            ->count();

        if ($validDemoContracts !== $expectedCounts['performance_contracts']) {
            throw new RuntimeException('Seluruh kontrak demo harus merujuk hanya ke siklus dan data terkait demo.');
        }
    }

    private function clearDemoData(): void
    {
        DB::table('audit_events')
            ->where(function (QueryBuilder $query): void {
                $query->where('actor_employee_id', 'like', 'DEMO-%')
                    ->orWhere('subject_id', 'like', 'DEMO-%');
            })
            ->delete();
        DB::table('performance_contracts')->where('demo_only', true)->delete();
        DB::table('cycles')->where('demo_only', true)->delete();
        DB::table('kpi_definitions')->where('demo_only', true)->delete();
        DB::table('assignments')->where('demo_only', true)->delete();
        DB::table('employee_role_grants')->where('demo_only', true)->delete();
        DB::table('employees')->where('demo_only', true)->delete();
        DB::table('positions')->where('demo_only', true)->delete();

        foreach (['SECTION', 'BRANCH', 'REGION', 'SUB_DEPARTMENT', 'DEPARTMENT', 'DIVISION', 'HEAD_OFFICE'] as $type) {
            DB::table('org_units')->where('demo_only', true)->where('type', $type)->delete();
        }
    }

    private function createOrganization(): array
    {
        $make = function (string $id, string $code, string $name, string $type, ?string $parentId = null): string {
            OrgUnit::query()->create([
                'id' => $id,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'demo_only' => true,
            ]);

            return $id;
        };

        $units = [
            'hq' => $make('DEMO-ORG-HQ', 'HQ-DEMO', 'Kantor Pusat Demo', 'HEAD_OFFICE'),
            'divisions' => [],
            'departments' => [],
            'subDepartments' => [],
            'regions' => [],
            'branches' => [],
            'sections' => [],
        ];

        for ($number = 1; $number <= 9; $number++) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $divisionName = $number === 1 ? 'Divisi Operasional' : "Divisi Demo $suffix";
            $departmentName = $number === 1 ? 'Bidang Operasional Demo' : "Bidang Demo $suffix";
            $subDepartmentName = $number === 1 ? 'Sub Bidang Operasional Demo' : "Sub Bidang Demo $suffix";
            $divisionId = $make("DEMO-ORG-DIV-$suffix", "DIV-$suffix", $divisionName, 'DIVISION', $units['hq']);
            $departmentId = $make("DEMO-ORG-BID-$suffix", "BID-$suffix", $departmentName, 'DEPARTMENT', $divisionId);
            $subDepartmentId = $make("DEMO-ORG-SUB-$suffix", "SUB-$suffix", $subDepartmentName, 'SUB_DEPARTMENT', $departmentId);
            $units['divisions'][$number] = $divisionId;
            $units['departments'][$number] = $departmentId;
            $units['subDepartments'][$number] = $subDepartmentId;
        }

        for ($number = 1; $number <= 4; $number++) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $units['regions'][$number] = $make("DEMO-ORG-KORWIL-$suffix", "KORWIL-$suffix", "Korwil Demo $suffix", 'REGION', $units['hq']);
        }

        for ($number = 1; $number <= 28; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $regionNumber = intdiv($number - 1, 7) + 1;
            $regionId = $units['regions'][$regionNumber];
            $branchId = $make("DEMO-ORG-CAB-$suffix", "CAB-$suffix", "Cabang Demo $suffix", 'BRANCH', $regionId);
            $marketingId = $make("DEMO-ORG-CAB-$suffix-MKT", "CAB-$suffix-MKT", "Pemasaran Demo $suffix", 'SECTION', $branchId);
            $operationsId = $make("DEMO-ORG-CAB-$suffix-OPS", "CAB-$suffix-OPS", "Operasional Demo $suffix", 'SECTION', $branchId);
            $units['branches'][$number] = $branchId;
            $units['sections'][$number] = ['marketing' => $marketingId, 'operations' => $operationsId];
        }

        return $units;
    }

    private function createPositions(): void
    {
        $definitions = [
            'ADMIN-KPI' => ['Admin KPI Demo', 'Admin KPI', 'ADMIN_KPI'],
            'DIR' => ['Direksi Demo', 'Pimpinan', 'DIREKSI'],
            'KADIV' => ['Kepala Divisi Demo', 'Pusat', 'KEPALA_DIVISI'],
            'KABID' => ['Kepala Bidang Demo', 'Pusat', 'KEPALA_BIDANG'],
            'KASUBBID' => ['Kepala Sub Bidang Demo', 'Pusat', 'KEPALA_SUB_BIDANG'],
            'STAF-PUSAT' => ['Staf Pusat Demo', 'Pusat', 'PEGAWAI'],
            'STAF-PUSAT-OPS' => ['Staf Sistem dan Jaringan TI', 'Pusat', 'PEGAWAI'],
            'KORWIL' => ['Korwil Demo', 'Wilayah', 'KORWIL'],
            'KACAB' => ['Kepala Cabang Demo', 'Cabang', 'KEPALA_CABANG'],
            'PEMASARAN' => ['Staf Pemasaran Demo', 'Cabang', 'PEGAWAI'],
            'OPERASIONAL' => ['Staf Operasional Demo', 'Cabang', 'PEGAWAI'],
        ];

        foreach ($definitions as $code => [$name, $group, $role]) {
            $id = 'DEMO-POS-'.$code;
            Position::query()->create([
                'id' => $id,
                'name' => $name,
                'group_name' => $group,
                'access_role' => $role,
                'demo_only' => true,
            ]);
            $this->positions[$code] = $id;
        }
    }

    private function createPeople(array $units): array
    {
        $profiles = [];

        $add = function (
            string $id,
            string $displayName,
            string $positionCode,
            string $orgUnitId,
            ?string $supervisorId = null,
            ?string $regionId = null,
        ) use (&$profiles): string {
            Employee::query()->create([
                'id' => $id,
                'display_name' => $displayName,
                'demo_only' => true,
                'active' => true,
            ]);

            Assignment::query()->create([
                'id' => 'DEMO-ASG-'.$id,
                'employee_id' => $id,
                'position_id' => $this->positions[$positionCode],
                'org_unit_id' => $orgUnitId,
                'supervisor_employee_id' => $supervisorId,
                'korwil_unit_id' => $regionId,
                'starts_at' => now()->startOfYear()->toDateString(),
                'ends_at' => null,
                'is_primary' => true,
                'demo_only' => true,
            ]);

            $profiles[$id] = [
                'employee_id' => $id,
                'position_code' => $positionCode,
                'supervisor_id' => $supervisorId,
                'org_unit_id' => $orgUnitId,
                'korwil_unit_id' => $regionId,
            ];

            return $id;
        };

        $add('DEMO-SDM-001', 'Admin KPI Demo 001', 'ADMIN-KPI', $units['hq']);

        $directors = [];
        for ($number = 1; $number <= 3; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $directors[$number] = $add("DEMO-DIR-$suffix", "Direksi Demo $suffix", 'DIR', $units['hq']);
        }

        for ($number = 1; $number <= 9; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $twoDigit = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $director = $directors[intdiv($number - 1, 3) + 1];
            $head = $add("DEMO-KADIV-$suffix", "Kepala Divisi Demo $twoDigit", 'KADIV', $units['divisions'][$number], $director);
            $sectionHead = $add("DEMO-KABID-$suffix", "Kepala Bidang Demo $twoDigit", 'KABID', $units['departments'][$number], $head);
            $subHead = $add("DEMO-KASUBBID-$suffix", "Kepala Sub Bidang Demo $twoDigit", 'KASUBBID', $units['subDepartments'][$number], $sectionHead);
            $staffName = $number === 1 ? 'Staf Sistem dan Jaringan TI Demo 001' : "Staf Pusat Demo $twoDigit";
            $staffPosition = $number === 1 ? 'STAF-PUSAT-OPS' : 'STAF-PUSAT';
            $staffOrgUnit = $number === 1 ? $units['divisions'][$number] : $units['subDepartments'][$number];
            $staffSupervisor = $number === 1 ? $head : $subHead;
            $add("DEMO-STAF-PUSAT-$suffix", $staffName, $staffPosition, $staffOrgUnit, $staffSupervisor);
        }

        for ($number = 1; $number <= 4; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $regionSuffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $director = $directors[($number - 1) % 3 + 1];
            $add("DEMO-KORWIL-$suffix", "Korwil Demo $regionSuffix", 'KORWIL', $units['regions'][$number], $director, $units['regions'][$number]);
        }

        for ($number = 1; $number <= 28; $number++) {
            $suffix = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $regionNumber = intdiv($number - 1, 7) + 1;
            $regionSuffix = str_pad((string) $regionNumber, 3, '0', STR_PAD_LEFT);
            $regionEmployee = "DEMO-KORWIL-$regionSuffix";
            $regionId = $units['regions'][$regionNumber];
            $branchHead = $add("DEMO-KACAB-$suffix", "Kepala Cabang Demo $suffix", 'KACAB', $units['branches'][$number], $regionEmployee, $regionId);
            $add("DEMO-PEMASARAN-$suffix", "Pemasaran Demo $suffix", 'PEMASARAN', $units['sections'][$number]['marketing'], $branchHead, $regionId);
            $add("DEMO-OPERASIONAL-$suffix", "Operasional Demo $suffix", 'OPERASIONAL', $units['sections'][$number]['operations'], $branchHead, $regionId);
        }

        return $profiles;
    }

    private function createDefinitions(): array
    {
        $definitions = [];
        $catalog = [
            ['A', 'unit_demo', 100, 0.20],
            ['A', 'item_demo', 50, 0.20],
            ['Q', '%_demo', 95, 0.15],
            ['L', '%_demo', 10, 0.15],
            ['Z', 'kejadian_demo', 0, 0.15],
            ['A', 'item_demo', 20, 0.15],
        ];

        foreach ($this->positions as $positionCode => $positionId) {
            if ($positionCode === 'ADMIN-KPI') {
                continue;
            }

            foreach ($catalog as $index => [$scoreType, $unit, $target, $weight]) {
                $number = $index + 1;
                $code = sprintf('DEMO-KPI-%s-%02d', $positionCode, $number);
                $definitions[$positionCode][] = KpiDefinition::query()->create([
                    'id' => $code,
                    'position_id' => $positionId,
                    'code' => $code,
                    'version' => 1,
                    'name' => sprintf('Indikator Demo %02d', $number),
                    'score_type' => $scoreType,
                    'definition' => 'Definisi sintetis untuk mencoba alur pengisian KPI.',
                    'unit' => $unit,
                    'example_target' => $target,
                    'weight' => $weight,
                    'source_note' => 'Data contoh buatan; bukan sumber operasional bank.',
                    'is_quality' => $scoreType === 'Q',
                    'application_note' => 'Aturan ini hanya untuk demo dan belum menjadi kebijakan KPI.',
                    'demo_only' => true,
                    'published' => true,
                ]);
            }
        }

        return $definitions;
    }

    private function createCycle(): KpiCycle
    {
        $startsAt = now()->startOfMonth();
        $endsAt = now()->endOfMonth();

        return KpiCycle::query()->create([
            'id' => 'DEMO-CYCLE-'.now()->format('Ym'),
            'name' => 'Siklus Demo '.now()->translatedFormat('F Y'),
            'cadence' => 'MONTHLY',
            'formula_version' => 'demo-prototype-v1',
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'status' => 'OPEN',
            'demo_only' => true,
        ]);
    }

    private function createContracts(array $profiles, array $definitions, KpiCycle $cycle, DemoScoreCalculator $scores): void
    {
        $statuses = ['DRAFT', 'SUBMITTED', 'REVISION', 'VERIFIED', 'FINAL'];
        $contractNumber = 0;
        $firstDraft = null;

        $selfAssignmentDemoEmployees = [
            'DEMO-KASUBBID-001',
            'DEMO-STAF-PUSAT-001',
            'DEMO-PEMASARAN-001',
            'DEMO-OPERASIONAL-001',
        ];

        foreach ($profiles as $employeeId => $profile) {
            if (! $profile['supervisor_id'] || in_array($employeeId, $selfAssignmentDemoEmployees, true)) {
                continue;
            }

            $contractNumber++;
            $status = $statuses[($contractNumber - 1) % count($statuses)];
            $employee = Employee::query()->findOrFail($profile['employee_id']);
            $assignment = $employee->activeAssignment()->with(['position', 'orgUnit'])->firstOrFail();
            $reviewer = Employee::query()->findOrFail($profile['supervisor_id']);
            $contractId = 'DEMO-CONTRACT-'.str_pad((string) $contractNumber, 4, '0', STR_PAD_LEFT);

            $contract = PerformanceContract::query()->create([
                'id' => $contractId,
                'cycle_id' => $cycle->id,
                'subject_type' => 'EMPLOYEE',
                'employee_id' => $employee->id,
                'org_unit_id' => $assignment->org_unit_id,
                'position_id' => $assignment->position_id,
                'reviewer_employee_id' => $reviewer->id,
                'assignment_snapshot' => [
                    'employee_id' => $employee->id,
                    'position_id' => $assignment->position_id,
                    'position_name' => $assignment->position->name,
                    'org_unit_id' => $assignment->org_unit_id,
                    'org_unit_name' => $assignment->orgUnit->name,
                    'reviewer_employee_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->display_name,
                ],
                'status' => $status,
                'demo_only' => true,
            ]);

            if ($status === 'DRAFT' && $firstDraft === null) {
                $firstDraft = [
                    'contract_id' => $contract->id,
                    'employee_id' => $employee->id,
                    'reviewer_employee_id' => $reviewer->id,
                ];
            }

            foreach ($definitions[$profile['position_code']] as $index => $definition) {
                $complete = $status !== 'DRAFT' || $index < 2;
                $target = (float) $definition->example_target;
                $actual = $complete ? $this->exampleActual($definition->score_type, $target, $contractNumber, $index) : null;
                $itemId = $contractId.'-ITEM-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

                ContractItem::query()->create([
                    'id' => $itemId,
                    'contract_id' => $contract->id,
                    'kpi_definition_id' => $definition->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'score_type' => $definition->score_type,
                    'unit' => $definition->unit,
                    'target' => $target,
                    'weight' => $definition->weight,
                    'actual' => $actual,
                    'state' => $complete ? 'VALID' : 'PENDING',
                    'reason' => null,
                    'note' => $complete ? 'Realisasi sintetis untuk demonstrasi.' : null,
                    'evidence' => $complete ? [[
                        'label' => 'Referensi demo',
                        'reference' => sprintf('DEMO-EVIDENCE-%04d-%02d', $contractNumber, $index + 1),
                    ]] : null,
                    'score' => null,
                ]);
            }

            $scores->recalculate($contract);

            if ($status !== 'DRAFT') {
                $this->audit(
                    $employee->id,
                    'CONTRACT_SUBMITTED',
                    'CONTRACT',
                    $contract->id,
                    ['status' => 'DRAFT'],
                    ['status' => 'SUBMITTED'],
                );
            }

            if ($status === 'REVISION') {
                $comment = 'Mohon periksa kembali realisasi dan referensi bukti demo.';
                ReviewEvent::query()->create([
                    'id' => $contractId.'-REVIEW-01',
                    'contract_id' => $contract->id,
                    'reviewer_employee_id' => $reviewer->id,
                    'decision' => 'REVISION',
                    'comment' => $comment,
                ]);
                $this->audit(
                    $reviewer->id,
                    'CONTRACT_REVISION',
                    'CONTRACT',
                    $contract->id,
                    ['status' => 'SUBMITTED'],
                    ['status' => 'REVISION', 'comment' => $comment],
                );
            } elseif ($status === 'VERIFIED') {
                $comment = 'Kontrak demo diverifikasi.';
                ReviewEvent::query()->create([
                    'id' => $contractId.'-REVIEW-01',
                    'contract_id' => $contract->id,
                    'reviewer_employee_id' => $reviewer->id,
                    'decision' => 'VERIFIED',
                    'comment' => $comment,
                ]);
                $this->audit(
                    $reviewer->id,
                    'CONTRACT_VERIFIED',
                    'CONTRACT',
                    $contract->id,
                    ['status' => 'SUBMITTED'],
                    ['status' => 'VERIFIED', 'comment' => $comment],
                );
            } elseif ($status === 'FINAL') {
                $reviewerComment = 'Kontrak demo diverifikasi.';
                ReviewEvent::query()->create([
                    'id' => $contractId.'-REVIEW-01',
                    'contract_id' => $contract->id,
                    'reviewer_employee_id' => $reviewer->id,
                    'decision' => 'VERIFIED',
                    'comment' => $reviewerComment,
                ]);
                $this->audit(
                    $reviewer->id,
                    'CONTRACT_VERIFIED',
                    'CONTRACT',
                    $contract->id,
                    ['status' => 'SUBMITTED'],
                    ['status' => 'VERIFIED', 'comment' => $reviewerComment],
                );

                $finalComment = 'Kontrak demo difinalisasi oleh SDM.';
                ReviewEvent::query()->create([
                    'id' => $contractId.'-REVIEW-02',
                    'contract_id' => $contract->id,
                    'reviewer_employee_id' => 'DEMO-SDM-001',
                    'decision' => 'FINAL',
                    'comment' => $finalComment,
                ]);
                $this->audit(
                    'DEMO-SDM-001',
                    'CONTRACT_FINAL',
                    'CONTRACT',
                    $contract->id,
                    ['status' => 'VERIFIED'],
                    ['status' => 'FINAL', 'comment' => $finalComment],
                );
            }
        }

        if ($firstDraft !== null) {
            $contract = PerformanceContract::query()->findOrFail($firstDraft['contract_id']);
            $item = $contract->items()->firstOrFail();
            $reason = 'Penyesuaian target untuk skenario demo; angka ini bukan kebijakan bank.';

            ContractComment::query()->create([
                'id' => $contract->id.'-COMMENT-01',
                'contract_id' => $contract->id,
                'actor_employee_id' => $firstDraft['employee_id'],
                'item_id' => $item->id,
                'message' => 'Mohon tinjau target indikator demo sebelum kontrak diajukan.',
            ]);
            ContractComment::query()->create([
                'id' => $contract->id.'-COMMENT-02',
                'contract_id' => $contract->id,
                'actor_employee_id' => $firstDraft['reviewer_employee_id'],
                'item_id' => $item->id,
                'message' => 'Target contoh disesuaikan untuk demo. Satuan dan realisasi tetap sintetis.',
            ]);

            $before = $this->itemState($item);
            $item->target = (float) $item->target + 10;
            $item->reason = $reason;
            $item->save();
            $scores->recalculate($contract);

            $this->audit(
                $firstDraft['reviewer_employee_id'],
                'CONTRACT_ITEM_UPDATED',
                'CONTRACT_ITEM',
                $item->id,
                $before,
                $this->itemState($item),
            );
        }
    }

    private function itemState(ContractItem $item): array
    {
        return [
            'target' => (float) $item->target,
            'weight' => (float) $item->weight,
            'actual' => $item->actual === null ? null : (float) $item->actual,
            'state' => $item->state,
            'reason' => $item->reason,
            'note' => $item->note,
            'evidence' => $item->evidence,
        ];
    }

    private function audit(
        string $actorEmployeeId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $before,
        array $after,
    ): void {
        AuditEvent::query()->create([
            'actor_employee_id' => $actorEmployeeId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    private function exampleActual(string $scoreType, float $target, int $contractNumber, int $itemIndex): float
    {
        if ($scoreType === 'Z') {
            return ($contractNumber + $itemIndex) % 4 === 0 ? 1 : 0;
        }

        $factors = [0.82, 1.00, 1.08, 0.93, 1.03];
        $factor = $factors[($contractNumber + $itemIndex) % count($factors)];
        $actual = $scoreType === 'L'
            ? $target * (1 - (($contractNumber + $itemIndex) % 3) * 0.04)
            : $target * $factor;

        if ($scoreType === 'Q') {
            $actual = min(100, $actual);
        }

        return round($actual, 4);
    }
}
