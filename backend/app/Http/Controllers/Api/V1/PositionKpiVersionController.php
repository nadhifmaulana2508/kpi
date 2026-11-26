<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiDefinition;
use App\Models\Position;
use App\Models\PositionKpiVersion;
use App\Services\AccessRoleResolver;
use App\Services\PositionKpiVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PositionKpiVersionController extends Controller
{
    public function index(Request $request, AccessRoleResolver $roles, PositionKpiVersionService $versions): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        abort_unless($roles->canManageKpi($employee), 403, 'Hanya Admin KPI yang dapat mengatur indikator jabatan.');

        $positions = Position::query()
            ->where('demo_only', true)
            ->orderBy('name')
            ->get()
            ->map(function (Position $position) use ($employee, $versions): array {
                $versions->activeForPosition($position, $employee->id);
                $positionVersions = PositionKpiVersion::query()
                    ->where('position_id', $position->id)
                    ->with('items.definition')
                    ->orderByDesc('version')
                    ->get();

                return [
                    'id' => $position->id,
                    'name' => $position->name,
                    'group_name' => $position->group_name,
                    'access_role' => $position->access_role,
                    'active_version_id' => $positionVersions->firstWhere('status', 'ACTIVE')?->id,
                    'versions' => $positionVersions->map(fn (PositionKpiVersion $version): array => $this->serializeVersion($version))->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $positions]);
    }

    public function update(
        Request $request,
        string $positionId,
        AccessRoleResolver $roles,
    ): JsonResponse {
        /** @var Employee $employee */
        $employee = $request->user();
        abort_unless($roles->canManageKpi($employee), 403, 'Hanya Admin KPI yang dapat mengatur indikator jabatan.');

        $position = Position::query()
            ->where('demo_only', true)
            ->findOrFail($positionId);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.definition_id' => ['nullable', 'string', 'exists:kpi_definitions,id'],
            'items.*.code' => ['required', 'string', 'max:80', 'distinct'],
            'items.*.name' => ['required', 'string', 'max:160'],
            'items.*.score_type' => ['required', 'in:A,Q,L,Z'],
            'items.*.definition' => ['required', 'string', 'max:3000'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.target' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'items.*.weight_percent' => ['required', 'numeric', 'gt:0', 'max:100'],
            'items.*.source_note' => ['nullable', 'string', 'max:3000'],
            'items.*.quality_flag' => ['required', 'boolean'],
            'items.*.application_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $weightTotal = collect($validated['items'])->sum(fn (array $item): float => (float) $item['weight_percent']);
        abort_if(abs($weightTotal - 100) >= 0.011, 422, 'Total bobot indikator harus 100%.');

        foreach ($validated['items'] as $item) {
            $target = (float) $item['target'];
            abort_if($item['score_type'] === 'Z' && $target !== 0.0, 422, 'Tipe Z memakai target 0.');
            abort_if($item['score_type'] !== 'Z' && $target <= 0, 422, 'Target KPI harus lebih dari 0.');
            abort_if(in_array($item['score_type'], ['Q', 'L'], true) && $target > 100, 422, 'Tipe Q/L memakai skala sampai 100.');

            if (! empty($item['definition_id'])) {
                $definition = KpiDefinition::query()
                    ->where('id', $item['definition_id'])
                    ->where('position_id', $position->id)
                    ->where('demo_only', true)
                    ->first();
                abort_if(! $definition, 422, 'Indikator tidak berasal dari jabatan yang dipilih.');
            }
        }

        $activeVersion = PositionKpiVersion::query()
            ->where('position_id', $position->id)
            ->where('status', 'ACTIVE')
            ->with('items.definition')
            ->orderByDesc('version')
            ->first();

        if ($activeVersion && $this->matchesActiveVersion($validated['items'], $activeVersion)) {
            return response()->json(['data' => $this->serializeVersion($activeVersion)]);
        }

        $newVersion = DB::transaction(function () use ($validated, $position, $employee, $activeVersion): PositionKpiVersion {
            if ($activeVersion) {
                $activeVersion->update(['status' => 'ARCHIVED']);
            }

            $version = PositionKpiVersion::query()->create([
                'id' => 'DEMO-POSITION-KPI-VERSION-'.Str::uuid(),
                'position_id' => $position->id,
                'version' => (int) PositionKpiVersion::query()->where('position_id', $position->id)->max('version') + 1,
                'status' => 'ACTIVE',
                'created_by_employee_id' => $employee->id,
            ]);

            $weightTotal = collect($validated['items'])->sum(fn (array $item): float => (float) $item['weight_percent']);
            $assignedWeight = 0.0;

            foreach (array_values($validated['items']) as $index => $item) {
                $definition = $this->resolveDefinition($position, $item);
                $weight = $index === count($validated['items']) - 1
                    ? round(1.0 - $assignedWeight, 4)
                    : round(((float) $item['weight_percent'] / $weightTotal), 4);
                $assignedWeight += $weight;

                $version->items()->create([
                    'id' => 'DEMO-POSITION-KPI-ITEM-'.Str::uuid(),
                    'kpi_definition_id' => $definition->id,
                    'target' => $item['target'],
                    'weight' => $weight,
                    'sort_order' => $index,
                ]);
            }

            $activeDefinitionIds = $version->items()->pluck('kpi_definition_id');
            KpiDefinition::query()
                ->where('position_id', $position->id)
                ->update(['published' => false]);
            KpiDefinition::query()
                ->whereIn('id', $activeDefinitionIds)
                ->update(['published' => true]);

            return $version->fresh(['items.definition']);
        });

        return response()->json(['data' => $this->serializeVersion($newVersion)]);
    }

    /** @param array<string, mixed> $item */
    private function resolveDefinition(Position $position, array $item): KpiDefinition
    {
        $existing = ! empty($item['definition_id'])
            ? KpiDefinition::query()->where('id', $item['definition_id'])->first()
            : null;

        $attributes = [
            'code' => $item['code'],
            'name' => $item['name'],
            'score_type' => $item['score_type'],
            'definition' => $item['definition'],
            'unit' => $item['unit'],
            'source_note' => $item['source_note'] ?? null,
            'is_quality' => (bool) $item['quality_flag'],
            'application_note' => $item['application_note'] ?? null,
        ];

        if ($existing && collect($attributes)->every(fn ($value, string $key): bool => $existing->{$key} === $value)) {
            return $existing;
        }

        $definitionVersion = (int) KpiDefinition::query()
            ->where('position_id', $position->id)
            ->where('code', $item['code'])
            ->max('version') + 1;

        return KpiDefinition::query()->create([
            'id' => 'DEMO-KPI-'.Str::uuid(),
            'position_id' => $position->id,
            'version' => $definitionVersion,
            'example_target' => $item['target'],
            'weight' => round(((float) $item['weight_percent']) / 100, 4),
            'demo_only' => true,
            'published' => true,
            ...$attributes,
        ]);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function matchesActiveVersion(array $items, PositionKpiVersion $version): bool
    {
        $existingItems = $version->items->values();
        if ($existingItems->count() !== count($items)) {
            return false;
        }

        foreach (array_values($items) as $index => $item) {
            $existing = $existingItems[$index];
            $definition = $existing->definition;
            $attributes = [
                'code' => $definition->code,
                'name' => $definition->name,
                'score_type' => $definition->score_type,
                'definition' => $definition->definition,
                'unit' => $definition->unit,
                'source_note' => $definition->source_note,
                'quality_flag' => $definition->is_quality,
                'application_note' => $definition->application_note,
            ];
            $incoming = [
                'code' => $item['code'],
                'name' => $item['name'],
                'score_type' => $item['score_type'],
                'definition' => $item['definition'],
                'unit' => $item['unit'],
                'source_note' => $item['source_note'] ?? null,
                'quality_flag' => (bool) $item['quality_flag'],
                'application_note' => $item['application_note'] ?? null,
            ];

            if (
                ($item['definition_id'] ?? null) !== $definition->id
                || $attributes !== $incoming
                || round((float) $item['target'], 4) !== round((float) $existing->target, 4)
                || round((float) $item['weight_percent'], 2) !== round((float) $existing->weight * 100, 2)
            ) {
                return false;
            }
        }

        return true;
    }

    private function serializeVersion(PositionKpiVersion $version): array
    {
        $version->loadMissing('position', 'items.definition');

        return [
            'id' => $version->id,
            'position_id' => $version->position_id,
            'position_name' => $version->position->name,
            'version' => $version->version,
            'status' => $version->status,
            'created_at' => $version->created_at?->toISOString(),
            'items' => $version->items->map(fn ($item): array => [
                'definition_id' => $item->kpi_definition_id,
                'code' => $item->definition->code,
                'name' => $item->definition->name,
                'score_type' => $item->definition->score_type,
                'definition' => $item->definition->definition,
                'unit' => $item->definition->unit,
                'target' => (float) $item->target,
                'weight_percent' => round((float) $item->weight * 100, 2),
                'source_note' => $item->definition->source_note ?? '',
                'quality_flag' => $item->definition->is_quality,
                'application_note' => $item->definition->application_note ?? '',
            ])->values(),
        ];
    }
}
