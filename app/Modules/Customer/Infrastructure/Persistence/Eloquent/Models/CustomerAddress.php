<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Domain\Enums\CustomerAddressType;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\Customer;

class CustomerAddress extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'customer_addresses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => CustomerAddressType::class,
            'is_default' => 'boolean',
            'geo_lat' => 'decimal:4',
            'geo_lng' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Country',
            'country_id'
        );
    }
}
