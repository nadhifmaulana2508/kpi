<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use UsesStringId;

    protected $table = 'employees';

    protected $fillable = ['id', 'display_name', 'demo_only', 'active'];

    protected function casts(): array
    {
        return [
            'demo_only' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class)
            ->where('is_primary', true)
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->latestOfMany('starts_at');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(PerformanceContract::class);
    }

    public function reviewQueue(): HasMany
    {
        return $this->hasMany(PerformanceContract::class, 'reviewer_employee_id');
    }

    public function ssoIdentities(): HasMany
    {
        return $this->hasMany(SsoIdentity::class);
    }
}
