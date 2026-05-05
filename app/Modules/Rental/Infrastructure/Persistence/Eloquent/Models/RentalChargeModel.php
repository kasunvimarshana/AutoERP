<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalChargeModel extends Model
{
    use HasTenant;

    protected $table = 'fleet_rental_charges';

    protected $fillable = [
        'tenant_id', 'rental_id',
        'charge_type', 'description',
        'quantity', 'unit_price', 'amount',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'quantity'   => 'string',
        'unit_price' => 'string',
        'amount'     => 'string',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(RentalModel::class, 'rental_id');
    }
}
