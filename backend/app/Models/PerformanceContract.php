<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceContract extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'cycle_id', 'subject_type', 'employee_id', 'org_unit_id', 'position_id',
        'reviewer_employee_id', 'assignment_snapshot', 'status', 'demo_only',
    ];

    protected function casts(): array
    {
        return [
            'assignment_snapshot' => 'array',
            'demo_only' => 'boolean',
        ];
    }

    public function scopeDemoOnly(Builder $query): Builder
    {
        return $query
            ->where($query->getModel()->qualifyColumn('demo_only'), true)
            ->whereHas('cycle', fn (Builder $cycle) => $cycle->where('demo_only', true))
            ->where(fn (Builder $contracts) => $contracts
                ->whereNull('employee_id')
                ->orWhereHas('employee', fn (Builder $employee) => $employee->where('demo_only', true)))
            ->where(fn (Builder $contracts) => $contracts
                ->whereNull('reviewer_employee_id')
                ->orWhereHas('reviewer', fn (Builder $reviewer) => $reviewer->where('demo_only', true)))
            ->where(fn (Builder $contracts) => $contracts
                ->whereNull('position_id')
                ->orWhereHas('position', fn (Builder $position) => $position->where('demo_only', true)))
            ->where(fn (Builder $contracts) => $contracts
                ->whereNull('org_unit_id')
                ->orWhereHas('orgUnit', fn (Builder $orgUnit) => $orgUnit->where('demo_only', true)))
            ->whereDoesntHave('items.definition', fn (Builder $definition) => $definition
                ->where('demo_only', false)
                ->orWhereHas('position', fn (Builder $position) => $position->where('demo_only', false)))
            ->whereDoesntHave('comments', fn (Builder $comment) => $comment
                ->whereHas('actor', fn (Builder $actor) => $actor->where('demo_only', false)))
            ->whereDoesntHave('reviewEvents', fn (Builder $event) => $event
                ->whereHas('reviewer', fn (Builder $reviewer) => $reviewer->where('demo_only', false)));
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(KpiCycle::class, 'cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class, 'contract_id')->latest();
    }

    public function reviewEvents(): HasMany
    {
        return $this->hasMany(ReviewEvent::class, 'contract_id')->latest();
    }
}
