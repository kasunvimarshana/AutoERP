<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierVehicleModel extends CoreModel
{
    protected $table = 'supplier_vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'vehicle_id' => 'integer',
            'vehicle_ownership_id' => 'integer',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'source_context' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
