<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'employee_id', 'position_id', 'org_unit_id', 'supervisor_employee_id',
        'korwil_unit_id', 'starts_at', 'ends_at', 'is_primary', 'demo_only',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_primary' => 'boolean',
            'demo_only' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_primary', true)
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            });
    }

    public function scopeDemoOnly(Builder $query): Builder
    {
        return $query
            ->where($query->getModel()->qualifyColumn('demo_only'), true)
            ->whereHas('employee', fn (Builder $query) => $query->where('demo_only', true))
            ->whereHas('position', fn (Builder $query) => $query->where('demo_only', true))
            ->whereHas('orgUnit', fn (Builder $query) => $query->where('demo_only', true));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function korwilUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'korwil_unit_id');
    }
}
