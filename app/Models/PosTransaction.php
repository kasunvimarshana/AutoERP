<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'business_location_id', 'cash_register_id', 'restaurant_table_id',
        'res_order_status', 'reference_no', 'status', 'customer_id', 'customer_group_id',
        'subtotal', 'discount_amount', 'tax_amount', 'total', 'paid_amount', 'change_amount',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'string',
            'discount_amount' => 'string',
            'tax_amount' => 'string',
            'total' => 'string',
            'paid_amount' => 'string',
            'change_amount' => 'string',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant\RestaurantTable::class, 'restaurant_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosTransactionLine::class);
    }

    public function transactionPayments(): HasMany
    {
        return $this->hasMany(PosTransactionPayment::class);
    }
}
