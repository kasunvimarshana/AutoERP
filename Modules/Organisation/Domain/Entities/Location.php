<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Location entity.
 *
 * A physical space within a Branch (e.g. warehouse floor, store counter).
 */
class Location extends Model
{
    use HasTenant;

    protected $table = 'locations';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
