<?php

declare(strict_types=1);

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class PaymentAllocation extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'payment_id',
        'allocatable_type',
        'allocatable_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'base_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the payment
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the allocatable (Invoice, PurchaseOrder, etc.) - polymorphic
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function allocatable()
    {
        return $this->morphTo();
    }

    /**
     * Get the creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
