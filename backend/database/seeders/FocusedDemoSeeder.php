<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\KpiDefinition;
use App\Models\OrgUnit;
use App\Models\Position;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FocusedDemoSeeder extends Seeder
{
    public function run(): void
    {
        $local = app()->environment('local');
        $isolatedUat = app()->environment('staging') && (bool) config('demo.data_allowed');

        if (! $local && ! $isolatedUat) {
            throw new RuntimeException('Pembaruan data awal hanya boleh dijalankan pada local atau UAT terisolasi dengan DEMO_DATA_ALLOWED=true.');
        }

        DB::transaction(function (): void {
            $this->clearDemoData();
            $units = $this->createOrganization();
            $positions = $this->createPositions();
            $this->call(OrganizationPositionCatalogSeeder::class);
            $this->createPeople($units, $positions);
            $this->createDefinitions($positions);
            $this->createCycle();
        });

        $this->command?->info('Data KCU Utama siap: 4 unit, jabatan Pusat/Wilayah/Cabang tersedia, 7 pegawai, 24 indikator, 1 periode, tanpa penilaian awal.');
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
        DB::table('cycle_position_kpi_versions')->delete();
        DB::table('position_kpi_versions')->delete();
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
        $make = static function (string $id, string $code, string $name, string $type, ?string $parentId = null): string {
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

        $branch = $make('DEMO-ORG-KCU-001', 'KCU-001', 'KCU Utama', 'BRANCH');
        $operations = $make('DEMO-ORG-KCU-OPS', 'KCU-OPS', 'Bidang Operasional', 'DEPARTMENT', $branch);
        $marketing = $make('DEMO-ORG-KCU-MKT', 'KCU-MKT', 'Bidang Pemasaran', 'DEPARTMENT', $branch);
        $credit = $make('DEMO-ORG-KCU-CREDIT', 'KCU-KREDIT', 'Sub Bidang Kredit', 'SUB_DEPARTMENT', $marketing);

        return compact('branch', 'operations', 'marketing', 'credit');
    }

    private function createPositions(): array
    {
        $definitions = [
            'ADMIN-KPI' => ['Admin KPI', 'Administrasi', 'ADMIN_KPI'],
            'KACAB' => ['Kepala Cabang', 'Pimpinan Cabang', 'KEPALA_CABANG'],
            'KABID-OPS' => ['Kepala Bidang Operasional', 'Operasional', 'KEPALA_BIDANG'],
            'KABID-MKT' => ['Kepala Bidang Pemasaran', 'Pemasaran', 'KEPALA_BIDANG'],
            'KASUBBID-KREDIT' => ['Kepala Sub Bidang Kredit', 'Kredit', 'KEPALA_SUB_BIDANG'],
            'AO-KREDIT' => ['Account Officer Kredit', 'Kredit', 'PEGAWAI'],
            'TELLER' => ['Teller', 'Operasional', 'PEGAWAI'],
        ];
        $positions = [];

        foreach ($definitions as $code => [$name, $group, $role]) {
            $id = 'DEMO-POS-'.$code;
            Position::query()->create([
                'id' => $id,
                'name' => $name,
                'group_name' => $group,
                'access_role' => $role,
                'demo_only' => true,
            ]);
            $positions[$code] = $id;
        }

        return $positions;
    }

    private function createPeople(array $units, array $positions): void
    {
        $people = [
            ['DEMO-SDM-001', 'Admin KPI', 'ADMIN-KPI', 'branch', null],
            ['DEMO-KACAB-001', 'Kepala Cabang', 'KACAB', 'branch', null],
            ['DEMO-KABID-OPS-001', 'Kepala Bidang Operasional', 'KABID-OPS', 'operations', 'DEMO-KACAB-001'],
            ['DEMO-KABID-001', 'Kepala Bidang Pemasaran', 'KABID-MKT', 'marketing', 'DEMO-KACAB-001'],
            ['DEMO-KASUBBID-001', 'Kepala Sub Bidang Kredit', 'KASUBBID-KREDIT', 'credit', 'DEMO-KABID-001'],
            ['DEMO-AO-KREDIT-001', 'Account Officer Kredit', 'AO-KREDIT', 'credit', 'DEMO-KASUBBID-001'],
            ['DEMO-OPERASIONAL-001', 'Teller', 'TELLER', 'operations', 'DEMO-KABID-OPS-001'],
        ];

        foreach ($people as [$id, $name, $positionCode, $unitKey, $supervisorId]) {
            Employee::query()->create([
                'id' => $id,
                'display_name' => $name,
                'demo_only' => true,
                'active' => true,
            ]);

            Assignment::query()->create([
                'id' => 'DEMO-ASG-'.$id,
                'employee_id' => $id,
                'position_id' => $positions[$positionCode],
                'org_unit_id' => $units[$unitKey],
                'supervisor_employee_id' => $supervisorId,
                'korwil_unit_id' => null,
                'starts_at' => now()->startOfYear()->toDateString(),
                'ends_at' => null,
                'is_primary' => true,
                'demo_only' => true,
            ]);
        }
    }

    private function createDefinitions(array $positions): void
    {
        $catalogByPosition = [
            'KACAB' => [
                ['01', 'Realisasi penyaluran kredit cabang', 'A', 'Rp juta', 125000, 0.35, 'Total penyaluran kredit selama periode penilaian.'],
                ['02', 'Pertumbuhan dana pihak ketiga', 'A', 'Rp juta', 150000, 0.25, 'Pertumbuhan dana pihak ketiga dibandingkan target periode.'],
                ['03', 'Kualitas portofolio kredit cabang', 'Q', '%', 98, 0.20, 'Persentase portofolio kredit yang memenuhi standar kualitas.'],
                ['04', 'Kepatuhan operasional cabang', 'Z', 'temuan', 0, 0.20, 'Jumlah temuan kepatuhan yang belum diselesaikan.'],
            ],
            'KABID-OPS' => [
                ['01', 'Akurasi transaksi operasional', 'Q', '%', 99.5, 0.30, 'Persentase transaksi operasional yang tercatat akurat.'],
                ['02', 'Pemenuhan SLA layanan cabang', 'Q', '%', 95, 0.25, 'Persentase layanan yang diselesaikan sesuai SLA.'],
                ['03', 'Akurasi rekonsiliasi kas', 'Q', '%', 100, 0.25, 'Persentase rekonsiliasi kas yang selesai tanpa selisih.'],
                ['04', 'Temuan kepatuhan operasional', 'Z', 'temuan', 0, 0.20, 'Jumlah temuan kepatuhan operasional yang belum diselesaikan.'],
            ],
            'KABID-MKT' => [
                ['01', 'Realisasi target penyaluran kredit', 'A', 'Rp juta', 75000, 0.35, 'Total penyaluran kredit pada bidang pemasaran.'],
                ['02', 'Pertumbuhan dana pihak ketiga', 'A', 'Rp juta', 90000, 0.25, 'Pertumbuhan dana pihak ketiga dibandingkan target periode.'],
                ['03', 'Kualitas portofolio kredit', 'Q', '%', 98, 0.20, 'Persentase portofolio kredit yang memenuhi standar kualitas.'],
                ['04', 'Penyelesaian tindak lanjut nasabah', 'Q', '%', 95, 0.20, 'Persentase tindak lanjut kebutuhan nasabah yang selesai.'],
            ],
            'KASUBBID-KREDIT' => [
                ['01', 'Realisasi kredit sub bidang', 'A', 'Rp juta', 35000, 0.35, 'Total penyaluran kredit yang dikelola sub bidang.'],
                ['02', 'Kelengkapan berkas kredit', 'Q', '%', 98, 0.25, 'Persentase berkas kredit yang lengkap saat diperiksa.'],
                ['03', 'Ketepatan analisis kredit', 'Q', '%', 95, 0.20, 'Persentase analisis kredit yang memenuhi standar waktu dan kelengkapan.'],
                ['04', 'Penyelesaian tindak lanjut kredit', 'Q', '%', 95, 0.20, 'Persentase tindak lanjut kredit yang selesai sesuai batas waktu.'],
            ],
            'AO-KREDIT' => [
                ['01', 'Realisasi penyaluran kredit', 'A', 'Rp juta', 1000, 0.40, 'Total nilai kredit yang direalisasikan selama periode.'],
                ['02', 'Kelengkapan dokumen pengajuan', 'Q', '%', 98, 0.20, 'Persentase pengajuan kredit dengan dokumen lengkap.'],
                ['03', 'Konversi pipeline kredit', 'Q', '%', 80, 0.20, 'Persentase pipeline yang berlanjut menjadi realisasi kredit.'],
                ['04', 'Kualitas portofolio kredit', 'Q', '%', 98, 0.20, 'Persentase portofolio kredit yang memenuhi standar kualitas.'],
            ],
            'TELLER' => [
                ['01', 'Akurasi transaksi teller', 'Q', '%', 99.8, 0.35, 'Persentase transaksi teller yang tercatat tanpa koreksi.'],
                ['02', 'Waktu layanan transaksi', 'L', 'menit', 5, 0.25, 'Rata-rata waktu layanan transaksi dibandingkan standar.'],
                ['03', 'Ketepatan rekonsiliasi kas', 'Q', '%', 100, 0.20, 'Persentase rekonsiliasi kas yang selesai sesuai prosedur.'],
                ['04', 'Temuan selisih kas', 'Z', 'temuan', 0, 0.20, 'Jumlah temuan selisih kas yang belum diselesaikan.'],
            ],
        ];

        foreach ($catalogByPosition as $positionCode => $catalog) {
            foreach ($catalog as [$suffix, $name, $type, $unit, $target, $weight, $definition]) {
                $id = sprintf('DEMO-KPI-%s-%s', $positionCode, $suffix);
                KpiDefinition::query()->create([
                    'id' => $id,
                    'position_id' => $positions[$positionCode],
                    'code' => $id,
                    'version' => 1,
                    'name' => $name,
                    'score_type' => $type,
                    'definition' => $definition,
                    'unit' => $unit,
                    'example_target' => $target,
                    'weight' => $weight,
                    'source_note' => 'Rekap kinerja unit dan catatan operasional periode berjalan.',
                    'is_quality' => $type === 'Q',
                    'application_note' => 'Isi realisasi sesuai periode penilaian dan lampirkan referensi pencatatan.',
                    'demo_only' => true,
                    'published' => true,
                ]);
            }
        }
    }

    private function createCycle(): void
    {
        $startsAt = now()->startOfMonth();

        KpiCycle::query()->create([
            'id' => 'DEMO-CYCLE-'.now()->format('Ym'),
            'name' => now()->translatedFormat('F Y'),
            'cadence' => 'MONTHLY',
            'formula_version' => 'kpi-80-20-v1',
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => now()->endOfMonth()->toDateString(),
            'status' => 'OPEN',
            'demo_only' => true,
        ]);
    }
}
