<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'contract_id', 'kpi_definition_id', 'code', 'name', 'score_type', 'unit', 'target',
        'weight', 'actual', 'state', 'reason', 'note', 'evidence', 'score',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:4',
            'weight' => 'decimal:4',
            'actual' => 'decimal:4',
            'evidence' => 'array',
            'score' => 'decimal:3',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PerformanceContract::class, 'contract_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id');
    }
}
