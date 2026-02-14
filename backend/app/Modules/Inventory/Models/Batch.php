<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Batch Model
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $sku_id
 * @property string $batch_number
 * @property \Carbon\Carbon $manufacturing_date
 * @property \Carbon\Carbon $expiry_date
 * @property int $quantity
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @package App\Modules\Inventory\Models
 */
class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'sku_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'status',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['batch_number', 'manufacturing_date', 'expiry_date', 'quantity', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function sku()
    {
        return $this->belongsTo(SKU::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
