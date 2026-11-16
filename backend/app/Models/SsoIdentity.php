<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoIdentity extends Model
{
    use UsesStringId;

    protected $fillable = ['id', 'employee_id', 'issuer', 'subject', 'active'];

    protected $hidden = ['identity_hash', 'subject'];

    public static function identityHash(string $issuer, string $subject): string
    {
        return hash('sha256', $issuer."\0".$subject);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SsoIdentity $identity): void {
            $identity->identity_hash = self::identityHash($identity->issuer, $identity->subject);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
