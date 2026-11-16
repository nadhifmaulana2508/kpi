<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ContractComment;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\PerformanceContract;
use App\Models\ReviewEvent;
use App\Services\DemoScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractWorkflowController extends Controller
{
    public function updateItem(
        Request $request,
        string $contract,
        string $item,
        DemoScoreCalculator $scores,
    ): JsonResponse {
        $validated = $request->validate([
            'target' => ['sometimes', 'numeric'],
            'weight' => ['sometimes', 'numeric', 'gt:0', 'lte:1'],
            'actual' => ['sometimes', 'nullable', 'numeric'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'evidence' => ['sometimes', 'array', 'max:10'],
            'evidence.*.label' => ['required_with:evidence', 'string', 'max:120'],
            'evidence.*.reference' => ['required_with:evidence', 'string', 'max:500'],
            'state' => ['sometimes', 'in:PENDING,VALID,NOT_APPLICABLE'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        /** @var Employee $actor */
        $actor = $request->user();

        $updatedItem = DB::transaction(function () use ($actor, $contract, $item, $validated, $scores): ContractItem {
            $record = $this->findContract($contract, true);
            $target = ContractItem::query()
                ->where('contract_id', $record->id)
                ->lockForUpdate()
                ->findOrFail($item);
            $role = $this->role($actor);

            abort_unless(in_array($record->status, ['DRAFT', 'REVISION'], true), 409, 'Kontrak sudah dikirim dan tidak dapat diubah.');

            $isOwner = $record->employee_id === $actor->id;
            $isReviewer = $record->reviewer_employee_id === $actor->id;
            $isSdm = $role === 'SDM';
            $hasTargetChange = array_key_exists('target', $validated) || array_key_exists('weight', $validated);
            $hasActualChange = array_key_exists('actual', $validated)
                || array_key_exists('note', $validated)
                || array_key_exists('evidence', $validated)
                || array_key_exists('state', $validated);

            abort_unless($isOwner || $isReviewer || $isSdm, 403, 'Identitas tidak berwenang mengubah item ini.');
            abort_if(! $hasTargetChange && ! $hasActualChange, 422, 'Tidak ada perubahan item yang dikirim.');
            abort_if($hasTargetChange && ! ($isReviewer || $isSdm), 403, 'Target dan bobot hanya dapat diubah reviewer atau SDM.');
            abort_if($hasActualChange && ! ($isOwner || $isSdm), 403, 'Realisasi hanya dapat diubah pegawai pemilik atau SDM.');

            $before = [
                'target' => (float) $target->target,
                'weight' => (float) $target->weight,
                'actual' => $target->actual === null ? null : (float) $target->actual,
                'state' => $target->state,
                'reason' => $target->reason,
                'note' => $target->note,
                'evidence' => $target->evidence,
            ];

            if ($hasTargetChange && blank($validated['reason'] ?? null)) {
                abort(422, 'Perubahan target atau bobot wajib menyertakan alasan.');
            }

            if (array_key_exists('target', $validated)) {
                $newTarget = (float) $validated['target'];
                abort_if($target->score_type === 'Z' && $newTarget !== 0.0, 422, 'Tipe Z memakai target demo 0.');
                abort_if($target->score_type !== 'Z' && $newTarget <= 0, 422, 'Target demo harus lebih dari 0.');
                abort_if(in_array($target->score_type, ['Q', 'L'], true) && $newTarget > 100, 422, 'Target tipe Q/L memakai skala demo sampai 100.');
                $target->target = $validated['target'];
            }
            if (array_key_exists('weight', $validated)) {
                $target->weight = $validated['weight'];
            }
            if (array_key_exists('actual', $validated)) {
                $target->actual = $validated['actual'];
            }
            if (array_key_exists('note', $validated)) {
                $target->note = $validated['note'];
            }
            if (array_key_exists('evidence', $validated)) {
                $target->evidence = $validated['evidence'];
            }
            if (array_key_exists('reason', $validated)) {
                $target->reason = $validated['reason'];
            }

            $requestedState = $validated['state'] ?? null;
            if ($requestedState === 'NOT_APPLICABLE') {
                abort_unless($isSdm, 403, 'Hanya SDM yang dapat menyetujui indikator tidak berlaku.');
                abort_if(blank($validated['reason'] ?? $target->reason), 422, 'Indikator tidak berlaku wajib memiliki alasan.');
                $target->actual = null;
                $target->state = 'NOT_APPLICABLE';
            } elseif ($requestedState === 'PENDING') {
                $target->state = 'PENDING';
            } elseif ($requestedState === 'VALID') {
                abort_if($target->actual === null || ! $this->hasEvidence($target->evidence), 422, 'Indikator valid wajib memiliki realisasi dan referensi bukti.');
                $target->state = 'VALID';
            } elseif ($hasActualChange) {
                $target->state = $target->actual !== null && $this->hasEvidence($target->evidence) ? 'VALID' : 'PENDING';
            }

            $target->save();
            $calculation = $scores->recalculate($record);
            abort_if(
                $target->state === 'VALID' && ($calculation['item_scores'][$target->id] ?? null) === null,
                422,
                'Nilai atau target belum sesuai dengan tipe skor indikator demo.',
            );

            $after = [
                'target' => (float) $target->target,
                'weight' => (float) $target->weight,
                'actual' => $target->actual === null ? null : (float) $target->actual,
                'state' => $target->state,
                'reason' => $target->reason,
                'note' => $target->note,
                'evidence' => $target->evidence,
            ];

            $this->audit($actor, 'CONTRACT_ITEM_UPDATED', 'CONTRACT_ITEM', $target->id, $before, $after);

            return $target->fresh();
        });

        $score = $scores->calculate($updatedItem->contract);

        return response()->json([
            'data' => [
                'id' => $updatedItem->id,
                'kpi_definition_id' => $updatedItem->kpi_definition_id,
                'code' => $updatedItem->code,
                'name' => $updatedItem->name,
                'score_type' => $updatedItem->score_type,
                'unit' => $updatedItem->unit,
                'target' => (float) $updatedItem->target,
                'weight' => (float) $updatedItem->weight,
                'actual' => $updatedItem->actual === null ? null : (float) $updatedItem->actual,
                'note' => $updatedItem->note ?? '',
                'evidence' => $updatedItem->evidence ?? [],
                'score' => $score['item_scores'][$updatedItem->id] ?? null,
                'state' => $updatedItem->state,
                'reason' => $updatedItem->reason,
            ],
        ]);
    }

    public function comment(Request $request, string $contract): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['nullable', 'string', 'exists:contract_items,id'],
            'message' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        /** @var Employee $actor */
        $actor = $request->user();
        $comment = DB::transaction(function () use ($actor, $contract, $validated): ContractComment {
            $record = $this->findContract($contract, true);
            $role = $this->role($actor);
            $authorized = $role === 'SDM'
                || $record->employee_id === $actor->id
                || $record->reviewer_employee_id === $actor->id;

            abort_unless($authorized, 403, 'Identitas tidak berwenang memberi komentar pada kontrak ini.');
            abort_unless(in_array($record->status, ['DRAFT', 'REVISION'], true), 409, 'Komentar hanya dapat ditambahkan pada Draf atau Revisi.');

            if (! empty($validated['item_id'])) {
                abort_unless($record->items()->whereKey($validated['item_id'])->exists(), 404);
            }

            $comment = ContractComment::query()->create([
                'id' => 'DEMO-COMMENT-'.Str::uuid(),
                'contract_id' => $record->id,
                'actor_employee_id' => $actor->id,
                'item_id' => $validated['item_id'] ?? null,
                'message' => $validated['message'],
            ]);

            $this->audit($actor, 'CONTRACT_COMMENT_ADDED', 'CONTRACT', $record->id, null, [
                'comment_id' => $comment->id,
                'item_id' => $comment->item_id,
                'message' => $comment->message,
            ]);

            return $comment->load('actor');
        });

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'actor_employee_id' => $comment->actor_employee_id,
                'actor_name' => $comment->actor?->display_name ?? 'Identitas demo',
                'item_id' => $comment->item_id,
                'message' => $comment->message,
                'created_at' => $comment->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function submit(
        Request $request,
        string $contract,
        ContractController $presenter,
        DemoScoreCalculator $scores,
    ): JsonResponse {
        /** @var Employee $actor */
        $actor = $request->user();
        $record = DB::transaction(function () use ($actor, $contract, $scores): PerformanceContract {
            $record = $this->findContract($contract, true);
            $role = $this->role($actor);
            abort_unless($record->employee_id === $actor->id || $role === 'SDM', 403, 'Hanya pegawai pemilik atau SDM yang dapat mengajukan kontrak.');
            abort_unless(in_array($record->status, ['DRAFT', 'REVISION'], true), 409, 'Status kontrak tidak dapat diajukan.');

            $record->load('items');
            abort_if($record->items->isEmpty(), 422, 'Kontrak belum memiliki indikator.');
            abort_if($record->items->contains(fn (ContractItem $item) => ! in_array($item->state, ['VALID', 'NOT_APPLICABLE'], true)), 422, 'Semua indikator wajib lengkap sebelum diajukan.');

            $reviewer = Employee::query()->find($record->reviewer_employee_id);
            abort_unless($reviewer && $reviewer->active && $reviewer->activeAssignment()->exists(), 422, 'Reviewer aktif tidak tersedia.');

            $score = $scores->recalculate($record);
            abort_if($score['kpi_score'] === null, 422, $score['message'] ?? 'Skor KPI demo belum siap dihitung.');

            $before = ['status' => $record->status];
            $record->status = 'SUBMITTED';
            $record->save();
            $this->audit($actor, 'CONTRACT_SUBMITTED', 'CONTRACT', $record->id, $before, ['status' => $record->status]);

            return $record->fresh(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor']);
        });

        return response()->json(['data' => $presenter->serialize($record, $scores)]);
    }

    public function review(
        Request $request,
        string $contract,
        ContractController $presenter,
        DemoScoreCalculator $scores,
    ): JsonResponse {
        $validated = $request->validate([
            'decision' => ['required', 'in:VERIFIED,REVISION,FINAL'],
            'comment' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        /** @var Employee $actor */
        $actor = $request->user();
        $record = DB::transaction(function () use ($actor, $contract, $validated, $scores): PerformanceContract {
            $record = $this->findContract($contract, true);
            $role = $this->role($actor);
            $decision = $validated['decision'];

            if ($decision === 'FINAL') {
                abort_unless($role === 'SDM', 403, 'Hanya SDM yang dapat memfinalisasi kontrak.');
                abort_unless($record->status === 'VERIFIED', 409, 'Kontrak harus terverifikasi sebelum difinalisasi.');
            } else {
                abort_unless($record->reviewer_employee_id === $actor->id, 403, 'Hanya reviewer yang tercatat dapat meninjau kontrak.');
                abort_unless($record->status === 'SUBMITTED', 409, 'Kontrak belum berada pada antrean tinjauan.');

                if ($decision === 'VERIFIED') {
                    $score = $scores->calculate($record);
                    abort_if($score['kpi_score'] === null, 422, $score['message'] ?? 'Skor KPI demo belum siap ditinjau.');
                }
            }

            $before = ['status' => $record->status];
            $record->status = $decision;
            $record->save();

            ReviewEvent::query()->create([
                'id' => 'DEMO-REVIEW-'.Str::uuid(),
                'contract_id' => $record->id,
                'reviewer_employee_id' => $actor->id,
                'decision' => $decision,
                'comment' => $validated['comment'],
            ]);

            $this->audit($actor, 'CONTRACT_'.$decision, 'CONTRACT', $record->id, $before, [
                'status' => $record->status,
                'comment' => $validated['comment'],
            ]);

            return $record->fresh(['employee', 'position', 'orgUnit', 'reviewer', 'items', 'comments.actor']);
        });

        return response()->json(['data' => $presenter->serialize($record, $scores)]);
    }

    private function findContract(string $id, bool $lock = false): PerformanceContract
    {
        $query = PerformanceContract::query()->demoOnly();
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function role(Employee $employee): string
    {
        return $employee->activeAssignment()->with('position')->firstOrFail()->position->access_role;
    }

    private function hasEvidence(?array $evidence): bool
    {
        return is_array($evidence) && collect($evidence)->contains(
            fn ($item) => is_array($item) && filled($item['label'] ?? null) && filled($item['reference'] ?? null),
        );
    }

    private function audit(
        Employee $actor,
        string $action,
        string $subjectType,
        string $subjectId,
        ?array $before,
        ?array $after,
    ): void {
        AuditEvent::query()->create([
            'actor_employee_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }
}
