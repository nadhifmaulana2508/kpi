<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CyclePositionKpiVersion extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'cycle_id', 'position_id', 'position_kpi_version_id'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(KpiCycle::class, 'cycle_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PositionKpiVersion::class, 'position_kpi_version_id');
    }
}
