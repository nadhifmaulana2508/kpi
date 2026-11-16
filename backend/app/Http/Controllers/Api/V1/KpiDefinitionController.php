<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KpiDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiDefinitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['nullable', 'string', 'exists:positions,id'],
            'group' => ['nullable', 'in:Bisnis,Operasional,Pendukung dan Kontrol,Kepemimpinan'],
            'search' => ['nullable', 'string', 'max:120'],
            'draft' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        $query = KpiDefinition::query()
            ->where('demo_only', true)
            ->whereHas('position', fn ($builder) => $builder->where('demo_only', true))
            ->with('position');

        if ($assignment->position->access_role !== 'SDM') {
            $query->where('position_id', $assignment->position_id)->where('published', true);
        } elseif (! empty($validated['position_id'])) {
            $query->where('position_id', $validated['position_id']);
        }

        if ($assignment->position->access_role === 'SDM' && array_key_exists('draft', $validated)) {
            $query->where('published', ! (bool) $validated['draft']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%'));
        }

        if (array_key_exists('draft', $validated) && (bool) $validated['draft']) {
            $query->where('published', false);
        }

        if (! empty($validated['group'])) {
            $group = $validated['group'];
            $query->whereHas('position', function ($builder) use ($group): void {
                if ($group === 'Kepemimpinan') {
                    $builder->whereIn('access_role', ['DIREKSI', 'KEPALA_DIVISI', 'KEPALA_BIDANG', 'KEPALA_SUB_BIDANG', 'KORWIL', 'KEPALA_CABANG']);
                } elseif ($group === 'Bisnis') {
                    $builder->where('name', 'like', '%Pemasaran%');
                } elseif ($group === 'Operasional') {
                    $builder->where('name', 'like', '%Operasional%');
                } else {
                    $builder->whereIn('access_role', ['SDM', 'PEGAWAI'])
                        ->whereNotIn('name', ['Staf Pemasaran Demo', 'Staf Operasional Demo']);
                }
            });
        }

        $page = $query->orderBy('position_id')->orderBy('code')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => $page->getCollection()->map(fn (KpiDefinition $definition) => $this->serialize($definition))->values(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(Request $request, string $definition): JsonResponse
    {
        $record = KpiDefinition::query()
            ->where('demo_only', true)
            ->whereHas('position', fn ($builder) => $builder->where('demo_only', true))
            ->where('published', true)
            ->with('position')
            ->findOrFail($definition);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        abort_unless(
            $assignment->position->access_role === 'SDM'
            || $record->position_id === $assignment->position_id,
            403,
            'Definisi KPI berada di luar jabatan demo aktif.',
        );

        return response()->json(['data' => $this->serialize($record)]);
    }

    private function serialize(KpiDefinition $definition): array
    {
        $role = $definition->position->access_role;
        $name = $definition->position->name;
        $group = match (true) {
            in_array($role, ['DIREKSI', 'KEPALA_DIVISI', 'KEPALA_BIDANG', 'KEPALA_SUB_BIDANG', 'KORWIL', 'KEPALA_CABANG'], true) => 'Kepemimpinan',
            str_contains($name, 'Pemasaran') => 'Bisnis',
            str_contains($name, 'Operasional') => 'Operasional',
            default => 'Pendukung dan Kontrol',
        };

        return [
            'id' => $definition->id,
            'code' => $definition->code,
            'version' => $definition->version,
            'name' => $definition->name,
            'group' => $group,
            'position_id' => $definition->position_id,
            'type' => $definition->score_type,
            'definition' => $definition->definition,
            'unit' => $definition->unit,
            'default_weight' => (float) $definition->weight,
            'sample_target' => (float) $definition->example_target,
            'target_note' => $definition->application_note ?? '',
            'data_source' => $definition->source_note ?? '',
            'quality_flag' => $definition->is_quality,
            'draft' => ! $definition->published,
            'demo_only' => $definition->demo_only,
        ];
    }
}
