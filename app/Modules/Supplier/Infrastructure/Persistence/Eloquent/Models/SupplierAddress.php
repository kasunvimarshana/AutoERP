<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Domain\Enums\SupplierAddressType;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class SupplierAddress extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'supplier_addresses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => SupplierAddressType::class,
            'is_default' => 'boolean',
            'geo_lat' => 'decimal:4',
            'geo_lng' => 'decimal:4',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Country',
            'country_id'
        );
    }
}
