<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AssetModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'asset_code',
        'asset_name',
        'usage_mode',
        'lifecycle_status',
        'rental_status',
        'service_status',
        'product_id',
        'serial_id',
        'supplier_id',
        'warehouse_id',
        'currency_id',
        'created_by',
        'registration_number',
        'chassis_number',
        'engine_number',
        'year_of_manufacture',
        'make',
        'model',
        'color',
        'fuel_type',
        'purchase_cost',
        'book_value',
        'purchase_date',
        'current_odometer',
        'engine_hours',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'serial_id' => 'integer',
        'supplier_id' => 'integer',
        'warehouse_id' => 'integer',
        'currency_id' => 'integer',
        'created_by' => 'integer',
        'year_of_manufacture' => 'integer',
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:6',
        'book_value' => 'decimal:6',
        'current_odometer' => 'decimal:6',
        'engine_hours' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel::class, 'product_id');
    }
}
