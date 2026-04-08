<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class WarehouseModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'description',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
        'contact_name', 'contact_email', 'contact_phone',
        'is_active', 'is_default', 'metadata',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocationModel::class, 'warehouse_id');
    }
}
