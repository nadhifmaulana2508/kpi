<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionKpiVersion extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'position_id', 'version', 'status', 'created_by_employee_id'];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PositionKpiVersionItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }
}
