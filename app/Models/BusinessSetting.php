<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-scoped key-value store for runtime-configurable system settings.
 *
 * Values are stored as strings; callers are responsible for casting to the
 * appropriate type. JSON values are stored as serialised JSON strings.
 */
class BusinessSetting extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'group',
        'is_public',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
