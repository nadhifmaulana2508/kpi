<?php

namespace App\Services;

use App\Models\CyclePositionKpiVersion;
use App\Models\KpiCycle;
use App\Models\KpiDefinition;
use App\Models\Position;
use App\Models\PositionKpiVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PositionKpiVersionService
{
    public function activeForPosition(Position $position, ?string $createdByEmployeeId = null): ?PositionKpiVersion
    {
        $activeVersion = PositionKpiVersion::query()
            ->where('position_id', $position->id)
            ->where('status', 'ACTIVE')
            ->with('items.definition')
            ->orderByDesc('version')
            ->first();

        if ($activeVersion) {
            return $activeVersion;
        }

        return DB::transaction(function () use ($position, $createdByEmployeeId): ?PositionKpiVersion {
            $activeVersion = PositionKpiVersion::query()
                ->where('position_id', $position->id)
                ->where('status', 'ACTIVE')
                ->with('items.definition')
                ->lockForUpdate()
                ->orderByDesc('version')
                ->first();

            if ($activeVersion) {
                return $activeVersion;
            }

            $definitions = KpiDefinition::query()
                ->where('position_id', $position->id)
                ->where('demo_only', true)
                ->where('published', true)
                ->orderBy('code')
                ->orderBy('version')
                ->get();

            if ($definitions->isEmpty()) {
                return null;
            }

            $weightTotal = $definitions->sum(fn (KpiDefinition $definition): float => (float) $definition->weight);
            abort_if($weightTotal <= 0, 422, 'Bobot indikator jabatan belum valid.');

            $version = PositionKpiVersion::query()->create([
                'id' => 'DEMO-POSITION-KPI-VERSION-'.Str::uuid(),
                'position_id' => $position->id,
                'version' => (int) PositionKpiVersion::query()->where('position_id', $position->id)->max('version') + 1,
                'status' => 'ACTIVE',
                'created_by_employee_id' => $createdByEmployeeId,
            ]);

            $assignedWeight = 0.0;
            foreach ($definitions as $index => $definition) {
                $weight = $index === $definitions->count() - 1
                    ? round(1.0 - $assignedWeight, 4)
                    : round(((float) $definition->weight) / $weightTotal, 4);
                $assignedWeight += $weight;

                $version->items()->create([
                    'id' => 'DEMO-POSITION-KPI-ITEM-'.Str::uuid(),
                    'kpi_definition_id' => $definition->id,
                    'target' => $definition->example_target,
                    'weight' => $weight,
                    'sort_order' => $index,
                ]);
            }

            return $version->fresh(['items.definition']);
        });
    }

    public function versionForCyclePosition(KpiCycle $cycle, Position $position): ?PositionKpiVersion
    {
        $cycleVersion = CyclePositionKpiVersion::query()
            ->where('cycle_id', $cycle->id)
            ->where('position_id', $position->id)
            ->with('version.items.definition')
            ->first();

        if ($cycleVersion) {
            return $cycleVersion->version;
        }

        $activeVersion = $this->activeForPosition($position);
        if (! $activeVersion) {
            return null;
        }

        $cycleVersion = CyclePositionKpiVersion::query()->firstOrCreate(
            ['cycle_id' => $cycle->id, 'position_id' => $position->id],
            [
                'id' => 'DEMO-CYCLE-POSITION-KPI-'.Str::uuid(),
                'position_kpi_version_id' => $activeVersion->id,
            ],
        );

        return $cycleVersion->load('version.items.definition')->version;
    }

    public function snapshotOpenCycle(KpiCycle $cycle): void
    {
        if ($cycle->status !== 'OPEN') {
            return;
        }

        Position::query()
            ->where('demo_only', true)
            ->orderBy('id')
            ->get()
            ->each(fn (Position $position) => $this->versionForCyclePosition($cycle, $position));
    }
}
