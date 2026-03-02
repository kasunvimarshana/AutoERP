<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Department entity.
 *
 * A functional unit within a Location.
 */
class Department extends Model
{
    use HasTenant;

    protected $table = 'departments';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
