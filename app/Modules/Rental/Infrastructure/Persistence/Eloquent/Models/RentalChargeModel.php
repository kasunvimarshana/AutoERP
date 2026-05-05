<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalChargeModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_charges';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'rental_incident_id',
        'charge_type',
        'charge_direction',
        'currency_id',
        'amount',
        'tax_amount',
        'total_amount',
        'due_date',
        'status',
        'journal_entry_id',
        'payment_id',
        'reversal_of_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'rental_incident_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'payment_id' => 'integer',
        'reversal_of_id' => 'integer',
        'amount' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'due_date' => 'date',
        'metadata' => 'array',
    ];
}
