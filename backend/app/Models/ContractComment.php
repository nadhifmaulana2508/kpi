<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractComment extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'contract_id', 'actor_employee_id', 'item_id', 'message'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PerformanceContract::class, 'contract_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class, 'item_id');
    }
}
