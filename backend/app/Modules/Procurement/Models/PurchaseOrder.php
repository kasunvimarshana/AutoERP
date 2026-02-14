<?php

namespace App\Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * PurchaseOrder Model
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $supplier_id
 * @property string $po_number
 * @property \Carbon\Carbon $order_date
 * @property \Carbon\Carbon $expected_delivery_date
 * @property string $status
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property string $notes
 * @property int $approved_by
 * @property \Carbon\Carbon $approved_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @package App\Modules\Procurement\Models
 */
class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['po_number', 'status', 'total_amount', 'approved_by', 'approved_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'approved_by');
    }
}
