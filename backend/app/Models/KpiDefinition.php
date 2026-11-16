<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDefinition extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'position_id', 'code', 'version', 'name', 'score_type', 'definition', 'unit',
        'example_target', 'weight', 'source_note', 'is_quality', 'application_note', 'demo_only', 'published',
    ];

    protected function casts(): array
    {
        return [
            'example_target' => 'decimal:4',
            'weight' => 'decimal:4',
            'is_quality' => 'boolean',
            'demo_only' => 'boolean',
            'published' => 'boolean',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }
}
