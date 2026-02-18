<?php

namespace App\Modules\POS\Models;

use App\Models\Tenant;
use App\Modules\CRM\Models\Customer;
use App\Modules\POS\Enums\InvoiceStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'sales_order_id',
        'customer_id',
        'status',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'notes',
        'terms_and_conditions',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'status' => InvoiceStatus::class,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->paid_amount = $this->payments->sum('amount');
        $this->balance_amount = $this->total_amount - $this->paid_amount;
    }

    public function updateStatus(): void
    {
        if ($this->balance_amount <= 0) {
            $this->status = InvoiceStatus::PAID;
        } elseif ($this->paid_amount > 0) {
            $this->status = InvoiceStatus::PARTIALLY_PAID;
        } elseif ($this->due_date < now() && $this->balance_amount > 0) {
            $this->status = InvoiceStatus::OVERDUE;
        }
    }
}
