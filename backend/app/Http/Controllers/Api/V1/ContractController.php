<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\KpiDefinition;
use App\Models\PerformanceContract;
use App\Services\DemoScoreCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function store(Request $request, DemoScoreCalculator $scores): JsonResponse
    {
        $validated = $request->validate([
            'cycle_id' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.kpi_definition_id' => ['required', 'string', 'distinct'],
            'items.*.target' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()
            ->demoOnly()
            ->with(['position', 'orgUnit'])
            ->firstOrFail();

        abort_unless(
            in_array($assignment->position->access_role, ['PEGAWAI', 'KEPALA_SUB_BIDANG'], true),
            403,
            'Self-assignment hanya tersedia untuk staf dan kepala sub bidang.',
        );

        $cycleQuery = KpiCycle::query()
            ->where('demo_only', true)
            ->where('cadence', 'MONTHLY')
            ->where('status', 'OPEN');

        if (! empty($validated['cycle_id'])) {
            $cycleQuery->whereKey($validated['cycle_id']);
        }

        $cycle = $cycleQuery->orderByDesc('starts_at')->first();
        abort_if(! $cycle, 409, 'Belum ada siklus KPI bulanan yang terbuka.');

        $existing = PerformanceContract::query()
            ->demoOnly()
            ->where('cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();
        abort_if($existing, 409, 'Kontrak untuk siklus ini sudah tersedia.');

        $definitionIds = collect($validated['items'])
            ->pluck('kpi_definition_id')
            ->all();
        $definitions = KpiDefinition::query()
            ->where('demo_only', true)
            ->where('published', true)
            ->where('position_id', $assignment->position_id)
            ->whereIn('id', $definitionIds)
            ->get()
            ->keyBy('id');

        abort_if(
            $definitions->count() !== count($definitionIds),
            422,
            'Pilih KPI aktif yang memang tersedia untuk jabatan demo Anda.',
        );

        $items = collect($validated['items']);
        $catalogWeightTotal = $definitions->sum(fn (KpiDefinition $definition): float => (float) $definition->weight);
        abort_if($catalogWeightTotal <= 0, 422, 'Katalog KPI belum memiliki bobot standar yang valid.');

        $normalizedWeights = [];
        $assignedWeight = 0.0;

        foreach ($items->values() as $index => $item) {
            $definition = $definitions->get($item['kpi_definition_id']);
            $target = (float) $item['target'];

            abort_if($definition->score_type === 'Z' && $target !== 0.0, 422, 'Tipe Z memakai target 0.');
            abort_if($definition->score_type !== 'Z' && $target <= 0, 422, 'Target KPI harus lebih dari 0.');
            abort_if(in_array($definition->score_type, ['Q', 'L'], true) && $target > 100, 422, 'Tipe Q/L memakai skala sampai 100.');

            $catalogWeight = (float) $definition->weight;
            abort_if($catalogWeight <= 0, 422, 'Setiap KPI harus memiliki bobot standar lebih dari 0.');

            $isLastItem = $index === $items->count() - 1;
            $normalizedWeight = $isLastItem
                ? round(1.0 - $assignedWeight, 4)
                : round($catalogWeight / $catalogWeightTotal, 4);
            abort_if($normalizedWeight <= 0, 422, 'Bobot KPI tidak dapat dinormalisasi.');

            $normalizedWeights[$definition->id] = $normalizedWeight;
            $assignedWeight += $normalizedWeight;
        }

        $contract = DB::transaction(function () use ($employee, $assignment, $cycle, $items, $definitions, $normalizedWeights): PerformanceContract {
            $contract = PerformanceContract::query()->create([
                'id' => 'DEMO-CONTRACT-SELF-'.Str::uuid(),
                'cycle_id' => $cycle->id,
                'subject_type' => 'EMPLOYEE',
                'employee_id' => $employee->id,
                'org_unit_id' => $assignment->org_unit_id,
                'position_id' => $assignment->position_id,
                'reviewer_employee_id' => null,
                'assignment_snapshot' => [
                    'employee_id' => $employee->id,
                    'position_id' => $assignment->position_id,
                    'position_name' => $assignment->position->name,
                    'org_unit_id' => $assignment->org_unit_id,
                    'org_unit_name' => $assignment->orgUnit->name,
                    'reviewer_assignment' => 'Menunggu penentuan alur reviewer.',
                ],
                'status' => 'DRAFT',
                'demo_only' => true,
            ]);

            foreach ($items as $index => $item) {
                $definition = $definitions->get($item['kpi_definition_id']);
                ContractItem::query()->create([
                    'id' => $contract->id.'-ITEM-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'contract_id' => $contract->id,
                    'kpi_definition_id' => $definition->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'score_type' => $definition->score_type,
                    'unit' => $definition->unit,
                    'target' => $item['target'],
                    'weight' => $normalizedWeights[$definition->id],
                    'actual' => null,
                    'state' => 'PENDING',
                ]);
            }

            AuditEvent::query()->create([
                'actor_employee_id' => $employee->id,
                'action' => 'CONTRACT_SELF_ASSIGNED',
                'subject_type' => 'CONTRACT',
                'subject_id' => $contract->id,
                'before_state' => null,
                'after_state' => [
                    'cycle_id' => $cycle->id,
                    'kpi_definition_ids' => $items->pluck('kpi_definition_id')->all(),
                ],
            ]);

            return $contract->fresh(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor']);
        });

        return response()->json(['data' => $this->serialize($contract, $scores)], 201);
    }

    public function index(Request $request, DemoScoreCalculator $scores): JsonResponse
    {
        $validated = $request->validate([
            'cycle_id' => ['nullable', 'string', 'exists:cycles,id'],
            'status' => ['nullable', 'in:DRAFT,SUBMITTED,REVISION,VERIFIED,FINAL'],
            'org_unit_id' => ['nullable', 'string', 'exists:org_units,id'],
            'employee_id' => ['nullable', 'string', 'exists:employees,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $role = $employee->activeAssignment()->with('position')->firstOrFail()->position->access_role;

        $query = PerformanceContract::query()
            ->demoOnly()
            ->with(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor']);

        if ($role !== 'SDM') {
            $query->where(fn (Builder $visible) => $visible
                ->where('employee_id', $employee->id)
                ->orWhere('reviewer_employee_id', $employee->id));
        }

        if (! empty($validated['cycle_id'])) {
            $query->where('cycle_id', $validated['cycle_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['org_unit_id'])) {
            $query->where('org_unit_id', $validated['org_unit_id']);
        }
        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        $page = $query->orderBy('status')->orderBy('id')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => $page->getCollection()
                ->map(fn (PerformanceContract $contract) => $this->serialize($contract, $scores))
                ->values(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(Request $request, string $contract, DemoScoreCalculator $scores): JsonResponse
    {
        $record = PerformanceContract::query()
            ->demoOnly()
            ->with(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor'])
            ->findOrFail($contract);

        /** @var Employee $employee */
        $employee = $request->user();
        $role = $employee->activeAssignment()->with('position')->firstOrFail()->position->access_role;
        abort_unless(
            $role === 'SDM'
            || $record->employee_id === $employee->id
            || $record->reviewer_employee_id === $employee->id,
            403,
            'Kontrak berada di luar kewenangan identitas aktif.',
        );

        return response()->json(['data' => $this->serialize($record, $scores)]);
    }

    public function serialize(PerformanceContract $contract, DemoScoreCalculator $scores): array
    {
        $score = $scores->calculate($contract);

        return [
            'id' => $contract->id,
            'cycle_id' => $contract->cycle_id,
            'subject_type' => $contract->subject_type,
            'subject_id' => $contract->employee_id ?? $contract->org_unit_id,
            'employee_name' => $contract->employee?->display_name,
            'position_name' => $contract->position?->name,
            'org_unit_name' => $contract->orgUnit?->name ?? '',
            'status' => $contract->status,
            'reviewer_employee_id' => $contract->reviewer_employee_id,
            'assignment_snapshot' => $contract->assignment_snapshot,
            'items' => $contract->items->map(fn ($item) => [
                'id' => $item->id,
                'kpi_definition_id' => $item->kpi_definition_id,
                'code' => $item->code,
                'name' => $item->name,
                'score_type' => $item->score_type,
                'unit' => $item->unit,
                'target' => (float) $item->target,
                'weight' => (float) $item->weight,
                'actual' => $item->actual === null ? null : (float) $item->actual,
                'note' => $item->note ?? '',
                'evidence' => $item->evidence ?? [],
                'score' => $score['item_scores'][$item->id] ?? null,
                'state' => $item->state,
                'reason' => $item->reason,
            ])->values(),
            'behavior_evaluation' => null,
            'comments' => $contract->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'actor_employee_id' => $comment->actor_employee_id,
                'actor_name' => $comment->actor?->display_name ?? 'Identitas demo',
                'item_id' => $comment->item_id,
                'message' => $comment->message,
                'created_at' => $comment->created_at?->toISOString(),
            ])->values(),
            'score' => [
                'kpi_score' => $score['kpi_score'],
                'behavior_score' => $score['behavior_score'],
                'final_score' => $score['final_score'],
                'rating' => $score['rating'],
                'valid_weight' => $score['valid_weight'],
                'quality_gate' => false,
                'formula_version' => $score['formula_version'],
                'demo_only' => $score['demo_only'],
                'message' => $score['message'],
            ],
        ];
    }
}
