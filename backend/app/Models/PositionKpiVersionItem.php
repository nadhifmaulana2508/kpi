<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionKpiVersionItem extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'position_kpi_version_id', 'kpi_definition_id', 'target', 'weight', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:4',
            'weight' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PositionKpiVersion::class, 'position_kpi_version_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id');
    }
}
