<?php

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class POSTransaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'tenant_id',
        'transaction_number',
        'customer_id',
        'cashier_id',
        'transaction_date',
        'payment_method',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'change_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'transaction_number',
                'customer_id',
                'cashier_id',
                'transaction_date',
                'payment_method',
                'subtotal',
                'tax_amount',
                'discount_amount',
                'total_amount',
                'amount_paid',
                'change_amount',
                'status',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(POSTransactionItem::class);
    }
}
