<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'payment_number',
        'invoice_id',
        'payment_method',
        'status',
        'amount',
        'currency',
        'paid_at',
        'reference_number',
        'notes',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'string',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
