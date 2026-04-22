<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CustomerAddressModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'type',
        'label',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country_id',
        'is_default',
        'geo_lat',
        'geo_lng',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'country_id' => 'integer',
        'is_default' => 'boolean',
        'geo_lat' => 'decimal:7',
        'geo_lng' => 'decimal:7',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }
}
