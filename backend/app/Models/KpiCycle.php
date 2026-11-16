<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiCycle extends Model
{
    use UsesStringId;

    protected $table = 'cycles';

    protected $fillable = ['id', 'name', 'cadence', 'formula_version', 'starts_at', 'ends_at', 'status', 'demo_only'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'demo_only' => 'boolean',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(PerformanceContract::class, 'cycle_id');
    }
}
