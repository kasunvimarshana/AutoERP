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
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceSource extends TenantOwnedModel
{
    protected $table = 'invoice_sources';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InvoiceSource $source): void {
            if ($source->isDirty(['invoice_id', 'tenant_id'])) {
                throw new LogicException('Invoice source ownership and tenant association are immutable.');
            }

            $invoice = $source->invoice ?? Invoice::find($source->invoice_id);
            if ($invoice) {
                $status = $invoice->status instanceof InvoiceStatus
                    ? $invoice->status
                    : InvoiceStatus::tryFrom((string) $invoice->status);
                if ($status !== null && $status !== InvoiceStatus::Draft) {
                    throw new LogicException('Invoice sources of non-draft invoices cannot be modified.');
                }
            }
        });

        self::deleting(static function (InvoiceSource $source): void {
            $invoice = $source->invoice ?? Invoice::find($source->invoice_id);
            if ($invoice) {
                $status = $invoice->status instanceof InvoiceStatus
                    ? $invoice->status
                    : InvoiceStatus::tryFrom((string) $invoice->status);
                if ($status !== null && $status !== InvoiceStatus::Draft) {
                    throw new LogicException('Invoice sources of non-draft invoices cannot be deleted.');
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
            'source_id' => 'integer',
            'source_document_date' => 'date',
            'source_subtotal' => 'decimal:6',
            'source_adjustment_total' => 'decimal:6',
            'source_grand_total' => 'decimal:6',
            'invoiced_amount' => 'decimal:6',
            'allocated_adjustment_amount' => 'decimal:6',
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
