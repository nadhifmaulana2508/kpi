<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\PerformanceContract;
use App\Services\AccessRoleResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditEventController extends Controller
{
    public function index(Request $request, AccessRoleResolver $roles): JsonResponse
    {
        $validated = $request->validate([
            'contract_id' => ['nullable', 'string', 'exists:performance_contracts,id'],
            'object_type' => ['nullable', 'string', 'max:80'],
            'object_id' => ['nullable', 'string', 'max:120'],
            'actor_id' => ['nullable', 'string', 'exists:employees,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        $hasAdminAccess = $roles->canManageKpi($employee, $assignment);
        $demoContractIds = PerformanceContract::query()
            ->demoOnly()
            ->select('id');
        $demoItemIds = ContractItem::query()
            ->whereIn('contract_id', $demoContractIds)
            ->select('id');
        $query = AuditEvent::query()
            ->with('actor')
            ->where(function (Builder $events) use ($demoContractIds, $demoItemIds): void {
                $events->where(fn (Builder $contractEvents) => $contractEvents
                    ->where('subject_type', 'CONTRACT')
                    ->whereIn('subject_id', $demoContractIds))
                    ->orWhere(fn (Builder $itemEvents) => $itemEvents
                        ->where('subject_type', 'CONTRACT_ITEM')
                        ->whereIn('subject_id', $demoItemIds));
            })
            ->where(fn (Builder $events) => $events
                ->whereNull('actor_employee_id')
                ->orWhereHas('actor', fn (Builder $actors) => $actors->where('demo_only', true)));

        if (! $hasAdminAccess) {
            $contractIds = PerformanceContract::query()
                ->demoOnly()
                ->where(fn (Builder $contracts) => $contracts
                    ->where('employee_id', $employee->id)
                    ->orWhere('reviewer_employee_id', $employee->id))
                ->select('id');
            $itemIds = ContractItem::query()->whereIn('contract_id', $contractIds)->select('id');

            $query->where(function (Builder $events) use ($contractIds, $itemIds): void {
                $events->where(fn (Builder $contractEvents) => $contractEvents
                    ->where('subject_type', 'CONTRACT')
                    ->whereIn('subject_id', $contractIds))
                    ->orWhere(fn (Builder $itemEvents) => $itemEvents
                        ->where('subject_type', 'CONTRACT_ITEM')
                        ->whereIn('subject_id', $itemIds));
            });
        }

        if (! empty($validated['contract_id'])) {
            $contract = PerformanceContract::query()
                ->demoOnly()
                ->findOrFail($validated['contract_id']);

            abort_unless(
                $hasAdminAccess
                || $contract->employee_id === $employee->id
                || $contract->reviewer_employee_id === $employee->id,
                403,
                'Kontrak berada di luar kewenangan identitas aktif.',
            );

            $contractItemIds = ContractItem::query()
                ->where('contract_id', $contract->id)
                ->select('id');

            $query->where(fn (Builder $events) => $events
                ->where(fn (Builder $contractEvents) => $contractEvents
                    ->where('subject_type', 'CONTRACT')
                    ->where('subject_id', $contract->id))
                ->orWhere(fn (Builder $itemEvents) => $itemEvents
                    ->where('subject_type', 'CONTRACT_ITEM')
                    ->whereIn('subject_id', $contractItemIds)));
        }

        if (! empty($validated['object_type'])) {
            $query->where('subject_type', $validated['object_type']);
        }
        if (! empty($validated['object_id'])) {
            $query->where('subject_id', $validated['object_id']);
        }
        if (! empty($validated['actor_id'])) {
            $query->where('actor_employee_id', $validated['actor_id']);
        }
        if (! empty($validated['from'])) {
            $query->where('created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $query->where('created_at', '<=', $validated['to']);
        }

        $page = $query->latest('created_at')->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (AuditEvent $event): array => [
                'id' => (string) $event->id,
                'actor_id' => $event->actor_employee_id ?? '',
                'actor_name' => $event->actor?->display_name ?? 'Identitas tidak aktif',
                'action' => $event->action,
                'object_type' => $event->subject_type,
                'object_id' => $event->subject_id,
                'reason' => data_get($event->after_state, 'reason') ?? data_get($event->after_state, 'comment'),
                'occurred_at' => $event->created_at?->toISOString(),
                'before' => $event->before_state,
                'after' => $event->after_state,
            ])->values(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
