<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalDepositModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_deposits';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'currency_id',
        'held_amount',
        'released_amount',
        'forfeited_amount',
        'status',
        'held_at',
        'released_at',
        'payment_id',
        'journal_entry_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'currency_id' => 'integer',
        'payment_id' => 'integer',
        'journal_entry_id' => 'integer',
        'held_amount' => 'decimal:6',
        'released_amount' => 'decimal:6',
        'forfeited_amount' => 'decimal:6',
        'held_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];
}
