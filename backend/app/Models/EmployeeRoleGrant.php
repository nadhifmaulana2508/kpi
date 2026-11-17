<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRoleGrant extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id', 'employee_id', 'access_role', 'active', 'demo_only', 'starts_at', 'ends_at',
        'reason', 'granted_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'demo_only' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'granted_by_employee_id');
    }
}
