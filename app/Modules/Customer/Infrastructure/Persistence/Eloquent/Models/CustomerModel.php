<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class CustomerModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'code',
        'email',
        'phone',
        'billing_address',
        'shipping_address',
        'date_of_birth',
        'loyalty_tier',
        'credit_limit',
        'payment_terms',
        'currency',
        'tax_number',
        'status',
        'type',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'billing_address'  => 'array',
        'shipping_address' => 'array',
        'attributes'       => 'array',
        'metadata'         => 'array',
        'credit_limit'     => 'float',
    ];
}
