<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Contracts\Auditable as AuditableContract;
use Modules\Audit\Traits\Auditable;
use Modules\Billing\Enums\PaymentStatus;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Traits\TenantScoped;

class SubscriptionPayment extends Model implements AuditableContract
{
    use Auditable, HasFactory, TenantScoped;

    protected $table = 'subscription_payments';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'payment_code',
        'amount',
        'refunded_amount',
        'status',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'error_message',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded]);
    }

    public function getAuditableAttributes(): array
    {
        return [
            'payment_code',
            'amount',
            'status',
            'payment_method',
            'transaction_id',
        ];
    }
}
