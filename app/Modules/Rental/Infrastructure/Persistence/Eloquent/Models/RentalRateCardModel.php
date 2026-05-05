<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalRateCardModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_rate_cards';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'code',
        'name',
        'asset_id',
        'product_id',
        'customer_id',
        'billing_uom',
        'rate',
        'deposit_percentage',
        'priority',
        'valid_from',
        'valid_to',
        'status',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'product_id' => 'integer',
        'customer_id' => 'integer',
        'priority' => 'integer',
        'rate' => 'decimal:6',
        'deposit_percentage' => 'decimal:6',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];
}
