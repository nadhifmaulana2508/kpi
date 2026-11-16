<?php

namespace Database\Seeders;

use App\Models\KpiCycle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SelfAssignmentCycleSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Siklus self-assignment hanya boleh disiapkan di lingkungan lokal.');
        }

        $startsAt = now()->startOfMonth();
        $endsAt = now()->endOfMonth();
        $cycleId = 'DEMO-CYCLE-'.now()->format('Ym');

        DB::transaction(function () use ($startsAt, $endsAt, $cycleId): void {
            $existing = KpiCycle::query()->whereKey($cycleId)->first();

            if ($existing && ! $existing->demo_only) {
                throw new RuntimeException('ID siklus demo berbenturan dengan siklus non-demo.');
            }

            KpiCycle::query()
                ->where('demo_only', true)
                ->where('status', 'OPEN')
                ->where('id', '!=', $cycleId)
                ->update(['status' => 'CLOSED']);

            $cycle = $existing ?? new KpiCycle(['id' => $cycleId]);
            $cycle->fill([
                'name' => 'Siklus Demo '.now()->translatedFormat('F Y'),
                'cadence' => 'MONTHLY',
                'formula_version' => 'demo-prototype-v1',
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'status' => 'OPEN',
                'demo_only' => true,
            ]);
            $cycle->save();
        });

        $this->command?->info('Siklus demo bulanan dibuka. Kontrak demo pada siklus sebelumnya tetap disimpan.');
    }
}
