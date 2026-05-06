<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalBookingModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_bookings';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'booking_number',
        'customer_id',
        'rental_mode',
        'ownership_model',
        'status',
        'pickup_at',
        'return_due_at',
        'actual_return_at',
        'pickup_location',
        'return_location',
        'currency_id',
        'rate_plan',
        'rate_amount',
        'estimated_amount',
        'final_amount',
        'security_deposit_amount',
        'security_deposit_status',
        'partner_supplier_id',
        'terms_and_conditions',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'customer_id' => 'integer',
        'currency_id' => 'integer',
        'partner_supplier_id' => 'integer',
        'rate_amount' => 'decimal:6',
        'estimated_amount' => 'decimal:6',
        'final_amount' => 'decimal:6',
        'security_deposit_amount' => 'decimal:6',
        'pickup_at' => 'datetime',
        'return_due_at' => 'datetime',
        'actual_return_at' => 'datetime',
        'metadata' => 'array',
    ];
}
