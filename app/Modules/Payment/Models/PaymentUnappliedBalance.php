<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\UnappliedBalanceStatus;
use Modules\Tenant\Models\TenantModel;

final class PaymentUnappliedBalance extends TenantOwnedModel
{
    protected $table = 'payment_unapplied_balances';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::deleting(static function (PaymentUnappliedBalance $balance): void {
            $payment = $balance->relationLoaded('payment') ? $balance->payment : $balance->payment()->first();
            $status = $payment?->document_status instanceof PaymentDocumentStatus
                ? $payment->document_status
                : ($payment?->document_status !== null ? PaymentDocumentStatus::tryFrom((string) $payment->document_status) : null);

            if ($payment && $status !== null && $status !== PaymentDocumentStatus::Draft) {
                throw new LogicException('Unapplied balance records for non-draft payments cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'party_id' => 'integer',
            'source_id' => 'integer',
            'allocation_status' => PaymentAllocationState::class,
            'original_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'status' => UnappliedBalanceStatus::class,
            'metadata' => 'array',
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
