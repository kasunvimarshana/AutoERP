<?php

namespace App\Modules\POS\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\CRM\Models\Customer;
use App\Modules\POS\Enums\SalesOrderStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'warehouse_id',
        'user_id',
        'status',
        'order_date',
        'delivery_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'customer_notes',
        'terms_and_conditions',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => SalesOrderStatus::class,
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
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function canEdit(): bool
    {
        return $this->status->isEditable();
    }

    public function canCancel(): bool
    {
        return $this->status->isCancellable();
    }
}
