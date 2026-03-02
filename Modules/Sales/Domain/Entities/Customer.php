<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Customer entity.
 */
class Customer extends Model
{
    use HasTenant;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'customer_tier',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
