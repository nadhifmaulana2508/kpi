<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewEvent extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'contract_id', 'reviewer_employee_id', 'decision', 'comment'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PerformanceContract::class, 'contract_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }
}
