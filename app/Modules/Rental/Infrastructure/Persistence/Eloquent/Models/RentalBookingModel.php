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
        'booking_type',
        'fleet_source',
        'status',
        'scheduled_start_at',
        'scheduled_end_at',
        'actual_start_at',
        'actual_end_at',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'deposit_amount',
        'total_amount',
        'deposit_status',
        'ar_transaction_id',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'customer_id' => 'integer',
        'ar_transaction_id' => 'integer',
        'journal_entry_id' => 'integer',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'subtotal' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'deposit_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'metadata' => 'array',
    ];
}
