<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'name', 'group_name', 'access_role', 'demo_only'];

    protected function casts(): array
    {
        return ['demo_only' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function kpiDefinitions(): HasMany
    {
        return $this->hasMany(KpiDefinition::class);
    }
}
