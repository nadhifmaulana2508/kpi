<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;
use RuntimeException;

class OrganizationPositionCatalogSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, group_name: string, access_role: string}>
     */
    private const POSITIONS = [
        'DIREKSI' => ['name' => 'Direksi', 'group_name' => 'Pusat', 'access_role' => 'DIREKSI'],
        'KEPALA-DIVISI' => ['name' => 'Kepala Divisi', 'group_name' => 'Pusat', 'access_role' => 'KEPALA_DIVISI'],
        'KEPALA-BIDANG-PUSAT' => ['name' => 'Kepala Bidang Pusat', 'group_name' => 'Pusat', 'access_role' => 'KEPALA_BIDANG'],
        'KEPALA-SUB-BIDANG-PUSAT' => ['name' => 'Kepala Sub Bidang Pusat', 'group_name' => 'Pusat', 'access_role' => 'KEPALA_SUB_BIDANG'],
        'STAF-PUSAT' => ['name' => 'Staf Pusat', 'group_name' => 'Pusat', 'access_role' => 'PEGAWAI'],
        'KORWIL' => ['name' => 'Korwil', 'group_name' => 'Wilayah', 'access_role' => 'KORWIL'],
        'STAF-PEMASARAN' => ['name' => 'Staf Pemasaran', 'group_name' => 'Cabang', 'access_role' => 'PEGAWAI'],
        'STAF-OPERASIONAL' => ['name' => 'Staf Operasional', 'group_name' => 'Cabang', 'access_role' => 'PEGAWAI'],
    ];

    public function run(): void
    {
        $allowedEnvironment = app()->environment('local')
            || (app()->environment('staging') && (bool) config('demo.data_allowed'));

        if (! $allowedEnvironment) {
            throw new RuntimeException('Katalog jabatan demo hanya boleh diatur pada local atau UAT terisolasi.');
        }

        foreach (self::POSITIONS as $code => $attributes) {
            Position::query()->firstOrCreate(
                ['id' => 'DEMO-POS-'.$code],
                [...$attributes, 'demo_only' => true],
            );
        }

        $this->command?->info('Jabatan Pusat, Wilayah, dan Cabang tersedia di katalog KPI.');
    }
}
