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
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceBalance extends TenantOwnedModel
{
    protected $table = 'invoice_balances';

    protected static function booted(): void
    {
        self::updating(static function (InvoiceBalance $balance): void {
            if ($balance->isDirty(['invoice_id', 'tenant_id'])) {
                throw new LogicException('Invoice balance ownership and tenant association are immutable.');
            }
        });

        self::deleting(static function (InvoiceBalance $balance): void {
            $invoice = $balance->invoice ?? Invoice::find($balance->invoice_id);
            if ($invoice) {
                $status = $invoice->status instanceof InvoiceStatus
                    ? $invoice->status
                    : InvoiceStatus::tryFrom((string) $invoice->status);
                if ($status !== null && $status !== InvoiceStatus::Draft) {
                    throw new LogicException('Invoice balance records of non-draft invoices cannot be deleted.');
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
            'invoice_total' => 'decimal:6',
            'paid_amount' => 'decimal:6',
            'credit_allocated_amount' => 'decimal:6',
            'debit_allocated_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'status' => InvoiceBalanceStatus::class,
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
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
