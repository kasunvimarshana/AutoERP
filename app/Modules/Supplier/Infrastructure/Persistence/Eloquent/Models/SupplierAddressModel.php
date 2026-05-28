<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierAddressModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'supplier_addresses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'country_id' => 'integer',
            'is_default' => 'boolean',
            'is_default_billing' => 'boolean',
            'is_default_shipping' => 'boolean',
            'is_active' => 'boolean',
            'geo_lat' => 'decimal:4',
            'geo_lng' => 'decimal:4',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
