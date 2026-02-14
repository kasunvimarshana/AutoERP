<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * SKU Model
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property string $sku_code
 * @property string $name
 * @property string $barcode
 * @property float $cost_price
 * @property float $selling_price
 * @property int $reorder_level
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @package App\Modules\Inventory\Models
 */
class SKU extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'skus';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku_code',
        'name',
        'barcode',
        'cost_price',
        'selling_price',
        'reorder_level',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku_code', 'name', 'barcode', 'cost_price', 'selling_price', 'reorder_level', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
