<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * UnitOfMeasure entity.
 *
 * Represents a unit of measure (e.g. kg, ltr, pcs) within a tenant.
 */
class UnitOfMeasure extends Model
{
    use HasTenant;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
