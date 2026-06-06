<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Tenant\Models\TenantModel;

final class Payment extends CoreModel
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'party_id' => 'integer',
            'currency_id' => 'integer',
            'payment_type' => PaymentType::class,
            'direction' => PaymentDirection::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'unapplied_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'voided_by' => 'integer',
            'voided_at' => 'datetime',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class, 'payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }

    public function unappliedBalance(): HasOne
    {
        return $this->hasOne(PaymentUnappliedBalance::class, 'payment_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(PaymentReversal::class, 'payment_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'payment_id');
    }
}
