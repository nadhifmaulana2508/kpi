<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\KpiCycle;
use App\Models\PerformanceContract;
use App\Services\AccessRoleResolver;
use App\Services\DemoScoreCalculator;
use App\Services\PositionKpiVersionService;
use App\Services\ReviewerChainResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function store(
        Request $request,
        DemoScoreCalculator $scores,
        ReviewerChainResolver $reviewers,
        PositionKpiVersionService $positionKpiVersions,
    ): JsonResponse {
        $validated = $request->validate([
            'cycle_id' => ['nullable', 'string'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()
            ->demoOnly()
            ->with(['position', 'orgUnit'])
            ->firstOrFail();

        abort_unless(
            in_array($assignment->position->access_role, ['PEGAWAI', 'KEPALA_SUB_BIDANG', 'KEPALA_BIDANG'], true),
            403,
            'Penilaian mandiri belum tersedia untuk jabatan ini.',
        );

        $cycleQuery = KpiCycle::query()
            ->where('demo_only', true)
            ->where('status', 'OPEN');

        if (! empty($validated['cycle_id'])) {
            $cycleQuery->whereKey($validated['cycle_id']);
        }

        $cycle = $cycleQuery->orderByDesc('starts_at')->orderByDesc('created_at')->first();
        abort_if(! $cycle, 409, 'Admin belum membuka periode penilaian.');

        $existing = PerformanceContract::query()
            ->demoOnly()
            ->where('cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();
        abort_if($existing, 409, 'Kontrak untuk siklus ini sudah tersedia.');

        $positionKpiVersion = $positionKpiVersions->versionForCyclePosition($cycle, $assignment->position);
        abort_if(! $positionKpiVersion, 422, 'Admin belum menetapkan indikator KPI untuk jabatan Anda.');

        $versionItems = $positionKpiVersion->items->sortBy('sort_order')->values();
        abort_if($versionItems->isEmpty(), 422, 'Paket KPI jabatan belum memiliki indikator.');

        $weightTotal = $versionItems->sum(fn ($item): float => (float) $item->weight);
        abort_if(abs($weightTotal - 1.0) >= 0.0001, 422, 'Total bobot paket KPI jabatan harus 100%.');

        foreach ($versionItems as $versionItem) {
            $definition = $versionItem->definition;
            $target = (float) $versionItem->target;
            abort_if($definition->score_type === 'Z' && $target !== 0.0, 422, 'Tipe Z memakai target 0.');
            abort_if($definition->score_type !== 'Z' && $target <= 0, 422, 'Target KPI harus lebih dari 0.');
            abort_if(in_array($definition->score_type, ['Q', 'L'], true) && $target > 100, 422, 'Tipe Q/L memakai skala sampai 100.');
        }

        $contract = DB::transaction(function () use ($employee, $assignment, $cycle, $positionKpiVersion, $versionItems, $reviewers): PerformanceContract {
            $contract = PerformanceContract::query()->create([
                'id' => 'DEMO-CONTRACT-SELF-'.Str::uuid(),
                'cycle_id' => $cycle->id,
                'subject_type' => 'EMPLOYEE',
                'employee_id' => $employee->id,
                'org_unit_id' => $assignment->org_unit_id,
                'position_id' => $assignment->position_id,
                'position_kpi_version_id' => $positionKpiVersion->id,
                'reviewer_employee_id' => null,
                'assignment_snapshot' => [
                    'employee_id' => $employee->id,
                    'position_id' => $assignment->position_id,
                    'position_name' => $assignment->position->name,
                    'org_unit_id' => $assignment->org_unit_id,
                    'org_unit_name' => $assignment->orgUnit->name,
                    'kpi_package_version' => [
                        'id' => $positionKpiVersion->id,
                        'version' => $positionKpiVersion->version,
                        'items' => $versionItems->map(fn ($item): array => [
                            'definition_id' => $item->kpi_definition_id,
                            'code' => $item->definition->code,
                            'name' => $item->definition->name,
                            'score_type' => $item->definition->score_type,
                            'unit' => $item->definition->unit,
                            'target' => (float) $item->target,
                            'weight' => (float) $item->weight,
                        ])->all(),
                    ],
                    'reviewer_assignment' => 'Penilai ditentukan dari hirarki jabatan saat pengajuan.',
                ],
                'behavior_evaluation' => null,
                'status' => 'DRAFT',
                'demo_only' => true,
            ]);

            foreach ($versionItems as $index => $versionItem) {
                $definition = $versionItem->definition;
                ContractItem::query()->create([
                    'id' => $contract->id.'-ITEM-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'contract_id' => $contract->id,
                    'kpi_definition_id' => $definition->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'score_type' => $definition->score_type,
                    'unit' => $definition->unit,
                    'target' => $versionItem->target,
                    'weight' => $versionItem->weight,
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
                    'position_kpi_version_id' => $positionKpiVersion->id,
                    'position_kpi_version' => $positionKpiVersion->version,
                    'kpi_definition_ids' => $versionItems->pluck('kpi_definition_id')->all(),
                ],
            ]);

            $contract->load('employee');
            $resolvedSlots = $reviewers->resolve($contract);
            $slotSnapshot = [];
            foreach (['P1', 'P2'] as $slot) {
                $reviewer = $resolvedSlots[$slot];
                $slotSnapshot[$slot] = [
                    'employee_id' => $reviewer?->id,
                    'name' => $reviewer?->display_name,
                    'position_name' => $reviewer?->activeAssignment()->demoOnly()->with('position')->first()?->position?->name,
                    'state' => $reviewer ? 'READY' : 'SKIPPED',
                ];
            }
            $snapshot = $contract->assignment_snapshot ?? [];
            $snapshot['reviewer_slots'] = $slotSnapshot;
            $snapshot['active_reviewer_slots'] = collect($resolvedSlots)->filter()->keys()->values()->all();
            $snapshot['current_reviewer_slot'] = null;
            $contract->assignment_snapshot = $snapshot;
            $contract->save();

            return $contract->fresh(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor']);
        });

        return response()->json(['data' => $this->serialize($contract, $scores)], 201);
    }

    public function index(Request $request, DemoScoreCalculator $scores, AccessRoleResolver $roles): JsonResponse
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
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        $hasAdminAccess = $roles->canManageKpi($employee, $assignment);

        $query = PerformanceContract::query()
            ->demoOnly()
            ->with(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor', 'reviewEvents.reviewer']);

        if (! $hasAdminAccess) {
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

    public function show(Request $request, string $contract, DemoScoreCalculator $scores, AccessRoleResolver $roles): JsonResponse
    {
        $record = PerformanceContract::query()
            ->demoOnly()
            ->with(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor', 'reviewEvents.reviewer'])
            ->findOrFail($contract);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        abort_unless(
            $roles->canManageKpi($employee, $assignment)
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
        $contract->loadMissing('reviewEvents.reviewer');
        $reviewerSlots = data_get($contract->assignment_snapshot, 'reviewer_slots', []);

        return [
            'id' => $contract->id,
            'cycle_id' => $contract->cycle_id,
            'created_at' => $contract->created_at?->toISOString(),
            'subject_type' => $contract->subject_type,
            'subject_id' => $contract->employee_id ?? $contract->org_unit_id,
            'employee_name' => $contract->employee?->display_name,
            'position_name' => $contract->position?->name,
            'org_unit_name' => $contract->orgUnit?->name ?? '',
            'status' => $contract->status,
            'reviewer_employee_id' => $contract->reviewer_employee_id,
            'position_kpi_version' => data_get($contract->assignment_snapshot, 'kpi_package_version.version'),
            'assignment_snapshot' => $contract->assignment_snapshot,
            'behavior_evaluation' => $contract->behavior_evaluation,
            'review_workflow' => [
                'reviewer_slots' => $reviewerSlots,
                'active_slots' => data_get($contract->assignment_snapshot, 'active_reviewer_slots', []),
                'current_slot' => data_get($contract->assignment_snapshot, 'current_reviewer_slot'),
            ],
            'review_history' => $contract->reviewEvents
                ->sortBy('created_at')
                ->map(function ($event) use ($reviewerSlots): array {
                    $slot = collect($reviewerSlots)->search(fn ($reviewer) => ($reviewer['employee_id'] ?? null) === $event->reviewer_employee_id);

                    return [
                        'reviewer_employee_id' => $event->reviewer_employee_id,
                        'reviewer_name' => $event->reviewer?->display_name ?? 'Penilai tidak aktif',
                        'slot' => $slot === false ? null : $slot,
                        'decision' => $event->decision,
                        'comment' => $event->comment,
                        'created_at' => $event->created_at?->toISOString(),
                    ];
                })->values(),
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
            'behavior_evaluation' => $contract->behavior_evaluation,
            'comments' => $contract->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'actor_employee_id' => $comment->actor_employee_id,
                'actor_name' => $comment->actor?->display_name ?? 'Identitas pegawai',
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
