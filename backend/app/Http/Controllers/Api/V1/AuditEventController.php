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
    public function recordMenuAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menu_key' => ['required', 'string', 'in:overview,mine,reviews,assignment,periods,history,monitoring,catalog,mapping,positionKpis,positions,ssoMappings,audit,comparison,auditLog,mine-kpi,mine-behavior,mine-approval,mine-history'],
            'menu_label' => ['required', 'string', 'max:120'],
            'previous_menu' => ['nullable', 'string', 'in:overview,mine,reviews,assignment,periods,history,monitoring,catalog,mapping,positionKpis,positions,ssoMappings,audit,comparison,auditLog,mine-kpi,mine-behavior,mine-approval,mine-history'],
            'previous_menu_label' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $event = AuditEvent::query()->create([
            'actor_employee_id' => $employee->id,
            'action' => 'MENU_ACCESSED',
            'subject_type' => 'MENU',
            'subject_id' => $validated['menu_key'],
            'after_state' => [
                'menu_label' => $validated['menu_label'],
                'previous_menu' => $validated['previous_menu'] ?? null,
                'previous_menu_label' => $validated['previous_menu_label'] ?? null,
            ],
        ]);

        return response()->json(['data' => ['id' => (string) $event->id]], 201);
    }

    public function adminLog(Request $request, AccessRoleResolver $roles): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $assignment = $employee->activeAssignment()->with('position')->firstOrFail();
        abort_unless($roles->canManageKpi($employee, $assignment), 403, 'Audit log hanya tersedia untuk Admin KPI dan SDM.');

        $query = AuditEvent::query()
            ->with(['actor.activeAssignment.position', 'actor.activeAssignment.orgUnit']);

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function (Builder $events) use ($search): void {
                $events->where('actor_employee_id', 'like', '%'.$search.'%')
                    ->orWhereHas('actor', fn (Builder $actors) => $actors->where('display_name', 'like', '%'.$search.'%'));
            });
        }
        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }
        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $page = $query->latest('created_at')->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => $page->getCollection()->map(fn (AuditEvent $event): array => [
                'id' => (string) $event->id,
                'actor_id' => $event->actor_employee_id ?? '',
                'actor_name' => $event->actor?->display_name ?? 'Identitas tidak aktif',
                'actor_position_name' => $event->actor?->activeAssignment?->position?->name,
                'actor_org_unit_name' => $event->actor?->activeAssignment?->orgUnit?->name,
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
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

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
