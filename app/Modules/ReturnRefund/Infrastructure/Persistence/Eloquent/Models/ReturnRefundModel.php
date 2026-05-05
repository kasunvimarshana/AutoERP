<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ReturnRefundModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_return_refunds';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_id',
        'return_number',
        'status',
        'returned_at',
        'end_odometer',
        'actual_days',
        'rental_charge',
        'extra_charges',
        'damage_charges',
        'fuel_charges',
        'deposit_paid',
        'refund_amount',
        'refund_method',
        'inspection_notes',
        'notes',
        'damage_photos',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'rental_charge'  => 'string',
        'extra_charges'  => 'string',
        'damage_charges' => 'string',
        'fuel_charges'   => 'string',
        'deposit_paid'   => 'string',
        'refund_amount'  => 'string',
        'returned_at'    => 'datetime',
        'damage_photos'  => 'array',
        'metadata'       => 'array',
        'is_active'      => 'boolean',
    ];
}
