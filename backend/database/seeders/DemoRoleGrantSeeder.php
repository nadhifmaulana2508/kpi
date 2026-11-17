<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeRoleGrant;
use App\Models\KpiDefinition;
use App\Models\OrgUnit;
use App\Models\Position;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoRoleGrantSeeder extends Seeder
{
    public function run(): void
    {
        $allowedEnvironment = app()->environment('local')
            || (app()->environment('staging') && (bool) config('demo.data_allowed'));

        if (! $allowedEnvironment) {
            throw new RuntimeException('Role demo hanya boleh diatur pada local atau UAT terisolasi.');
        }

        $admin = Employee::query()
            ->whereKey('DEMO-SDM-001')
            ->where('demo_only', true)
            ->where('active', true)
            ->firstOrFail();
        $adminAssignment = $admin->activeAssignment()->demoOnly()->firstOrFail();
        $adminPosition = Position::query()->updateOrCreate(
            ['id' => 'DEMO-POS-ADMIN-KPI'],
            [
                'name' => 'Admin KPI Demo',
                'group_name' => 'Admin KPI',
                'access_role' => 'ADMIN_KPI',
                'demo_only' => true,
            ],
        );
        $admin->display_name = 'Admin KPI Demo 001';
        $admin->save();
        $adminAssignment->position_id = $adminPosition->id;
        $adminAssignment->save();
        Position::query()
            ->whereKey('DEMO-POS-SDM')
            ->where('demo_only', true)
            ->whereDoesntHave('assignments')
            ->delete();

        $employee = Employee::query()
            ->whereKey('DEMO-STAF-PUSAT-001')
            ->where('demo_only', true)
            ->where('active', true)
            ->firstOrFail();
        $assignment = $employee->activeAssignment()->demoOnly()->with('position')->firstOrFail();

        if ($assignment->position->access_role !== 'PEGAWAI') {
            throw new RuntimeException('Identitas demo pusat harus tetap memiliki role utama Staf/PEGAWAI.');
        }

        $position = Position::query()->updateOrCreate(
            ['id' => 'DEMO-POS-STAF-PUSAT-OPS'],
            [
                'name' => 'Staf Sistem dan Jaringan TI',
                'group_name' => 'Pusat',
                'access_role' => 'PEGAWAI',
                'demo_only' => true,
            ],
        );

        $division = OrgUnit::query()
            ->whereKey('DEMO-ORG-DIV-01')
            ->where('demo_only', true)
            ->where('type', 'DIVISION')
            ->firstOrFail();
        $division->name = 'Divisi Operasional';
        $division->save();

        $employee->display_name = 'Staf Sistem dan Jaringan TI Demo 001';
        $employee->save();

        OrgUnit::query()->whereKey('DEMO-ORG-BID-01')->update(['name' => 'Bidang Operasional Demo']);
        OrgUnit::query()->whereKey('DEMO-ORG-SUB-01')->update(['name' => 'Sub Bidang Operasional Demo']);

        $assignment->position_id = $position->id;
        $assignment->org_unit_id = $division->id;
        $assignment->supervisor_employee_id = 'DEMO-KADIV-001';
        $assignment->save();

        $definitions = [
            ['A', 'unit_demo', 100, 0.20],
            ['A', 'item_demo', 50, 0.20],
            ['Q', '%_demo', 95, 0.15],
            ['L', '%_demo', 10, 0.15],
            ['Z', 'kejadian_demo', 0, 0.15],
            ['A', 'item_demo', 20, 0.15],
        ];

        foreach ($definitions as $index => [$scoreType, $unit, $target, $weight]) {
            $number = $index + 1;
            $code = sprintf('DEMO-KPI-STAF-PUSAT-OPS-%02d', $number);
            KpiDefinition::query()->updateOrCreate(
                ['id' => $code],
                [
                    'position_id' => $position->id,
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
                ],
            );
        }

        EmployeeRoleGrant::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'access_role' => 'ADMIN_KPI'],
            [
                'id' => 'DEMO-ROLE-GRANT-STAF-PUSAT-001-ADMIN',
                'active' => true,
                'demo_only' => true,
                'starts_at' => now()->startOfYear()->toDateString(),
                'ends_at' => null,
                'reason' => 'Akses Admin KPI tambahan untuk simulasi dual role Staf Pusat Operasional.',
                'granted_by_employee_id' => $admin->id,
            ],
        );
    }
}
