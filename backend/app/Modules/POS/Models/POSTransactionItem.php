<?php

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class POSTransactionItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'pos_transaction_items';

    protected $fillable = [
        'tenant_id',
        'pos_transaction_id',
        'sku_id',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'pos_transaction_id',
                'sku_id',
                'quantity',
                'unit_price',
                'discount',
                'tax_rate',
                'line_total',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function posTransaction()
    {
        return $this->belongsTo(POSTransaction::class);
    }

    public function sku()
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\SKU::class);
    }
}
