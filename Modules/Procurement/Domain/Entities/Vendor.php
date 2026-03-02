<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Vendor entity.
 *
 * rating is cast to string for BCMath precision.
 */
class Vendor extends Model
{
    use HasTenant;

    protected $table = 'vendors';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'address',
        'vendor_code',
        'rating',
        'is_active',
    ];

    protected $casts = [
        'rating'    => 'string',
        'is_active' => 'boolean',
    ];
}
