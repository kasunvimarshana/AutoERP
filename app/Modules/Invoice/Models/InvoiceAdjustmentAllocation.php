<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceAdjustmentAllocation extends TenantOwnedModel
{
    protected $table = 'invoice_adjustment_allocations';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InvoiceAdjustmentAllocation $allocation): void {
            if ($allocation->isDirty(['invoice_id', 'tenant_id'])) {
                throw new LogicException('Invoice adjustment allocation ownership and tenant association are immutable.');
            }

            $invoice = $allocation->invoice ?? Invoice::find($allocation->invoice_id);
            if ($invoice) {
                $status = $invoice->status instanceof InvoiceStatus
                    ? $invoice->status
                    : InvoiceStatus::tryFrom((string) $invoice->status);
                if ($status !== null && $status !== InvoiceStatus::Draft) {
                    throw new LogicException('Invoice adjustment allocations of non-draft invoices cannot be modified.');
                }
            }
        });

        self::deleting(static function (InvoiceAdjustmentAllocation $allocation): void {
            $invoice = $allocation->invoice ?? Invoice::find($allocation->invoice_id);
            if ($invoice) {
                $status = $invoice->status instanceof InvoiceStatus
                    ? $invoice->status
                    : InvoiceStatus::tryFrom((string) $invoice->status);
                if ($status !== null && $status !== InvoiceStatus::Draft) {
                    throw new LogicException('Invoice adjustment allocations of non-draft invoices cannot be deleted.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_adjustment_id' => 'integer',
            'source_adjustment_id' => 'integer',
            'source_id' => 'integer',
            'adjustment_type' => AdjustmentType::class,
            'effect' => AdjustmentEffect::class,
            'allocation_method' => AllocationMethod::class,
            'source_amount' => 'decimal:6',
            'previously_allocated_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InvoiceAdjustment::class, 'invoice_adjustment_id');
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
