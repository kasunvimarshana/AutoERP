<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * StockMovement Model (Append-only ledger)
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $sku_id
 * @property int $batch_id
 * @property string $type
 * @property int $quantity
 * @property int $balance_after
 * @property string $reference_type
 * @property int $reference_id
 * @property string $notes
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * 
 * @package App\Modules\Inventory\Models
 */
class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'sku_id',
        'batch_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance_after' => 'integer',
        'reference_id' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'quantity', 'balance_after', 'reference_type', 'reference_id', 'notes'])
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

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
