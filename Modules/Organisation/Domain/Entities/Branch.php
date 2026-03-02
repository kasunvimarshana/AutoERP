<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Branch entity.
 *
 * A geographic or operational subdivision of an Organisation.
 */
class Branch extends Model
{
    use HasTenant;

    protected $table = 'branches';

    protected $fillable = [
        'tenant_id',
        'organisation_id',
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
