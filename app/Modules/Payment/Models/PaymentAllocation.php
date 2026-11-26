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
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Tenant\Models\TenantModel;

final class PaymentAllocation extends TenantOwnedModel
{
    public const ACTIVE_IDENTITY_SLOT = 1;

    protected $table = 'payment_allocations';

    protected static function booted(): void
    {
        self::updating(static function (PaymentAllocation $allocation): void {
            $originalStatus = $allocation->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof AllocationStatus
                ? $originalStatus
                : ($originalStatus !== null ? AllocationStatus::tryFrom((string) $originalStatus) : null);

            if ($originalStatusEnum === AllocationStatus::Active || $originalStatusEnum === AllocationStatus::Reversed) {
                $immutableAttributes = [
                    'invoice_id',
                    'payment_id',
                    'allocation_date',
                    'allocated_amount',
                    'tenant_id',
                    'organization_unit_id',
                    'invoice_number_snapshot',
                    'invoice_date_snapshot',
                    'invoice_currency_code_snapshot',
                    'invoice_exchange_rate_snapshot',
                    'invoice_total',
                    'invoice_balance_before',
                    'invoice_balance_after',
                    'previously_allocated_amount',
                    'allocation_method',
                ];
                if ($allocation->isDirty($immutableAttributes)) {
                    throw new LogicException('Payment allocation amounts and targets are immutable. Reverse the allocation instead.');
                }
            } else {
                $immutablePendingAttributes = [
                    'invoice_id',
                    'payment_id',
                    'allocation_date',
                    'allocated_amount',
                    'tenant_id',
                    'organization_unit_id',
                    'invoice_number_snapshot',
                    'invoice_currency_code_snapshot',
                    'invoice_exchange_rate_snapshot',
                    'allocation_method',
                ];
                if ($allocation->isDirty($immutablePendingAttributes)) {
                    throw new LogicException('Payment allocation amounts and targets are immutable. Reverse the allocation instead.');
                }
            }
        });

        self::deleting(static function (PaymentAllocation $allocation): void {
            $payment = $allocation->relationLoaded('payment') ? $allocation->payment : $allocation->payment()->first();
            $status = $payment?->document_status instanceof PaymentDocumentStatus
                ? $payment->document_status
                : ($payment?->document_status !== null ? PaymentDocumentStatus::tryFrom((string) $payment->document_status) : null);

            $allocationStatus = $allocation->status instanceof AllocationStatus
                ? $allocation->status
                : ($allocation->status !== null ? AllocationStatus::tryFrom((string) $allocation->status) : null);

            if ($payment && $status !== null && $status !== PaymentDocumentStatus::Draft) {
                throw new LogicException('Allocations of non-draft payments cannot be deleted. Use governed reversal to undo an allocation.');
            }
            if ($allocationStatus !== null && $allocationStatus !== AllocationStatus::Pending) {
                throw new LogicException('Non-pending payment allocations cannot be deleted. Use governed reversal to undo an allocation.');
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
            'invoice_id' => 'integer',
            'active_identity_slot' => 'integer',
            'invoice_date_snapshot' => 'date',
            'invoice_exchange_rate_snapshot' => 'decimal:6',
            'invoice_total' => 'decimal:6',
            'invoice_balance_before' => 'decimal:6',
            'previously_allocated_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'invoice_balance_after' => 'decimal:6',
            'allocation_date' => 'date',
            'status' => AllocationStatus::class,
            'realized_at' => 'datetime',
            'realized_by' => 'integer',
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
