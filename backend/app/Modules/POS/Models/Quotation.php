<?php

namespace App\Modules\POS\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\CRM\Models\Customer;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'quotation_number',
        'customer_id',
        'user_id',
        'status',
        'quotation_date',
        'valid_until',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'terms_and_conditions',
        'converted_to_sales_order_id',
        'converted_at',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedToSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_to_sales_order_id');
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function isValid(): bool
    {
        return $this->valid_until >= now()->toDateString();
    }

    public function isConverted(): bool
    {
        return ! is_null($this->converted_to_sales_order_id);
    }
}
