<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Enums\InvoiceDocumentKind;

final class InvoiceDocumentSnapshot extends TenantOwnedModel
{
    protected $table = 'invoice_document_snapshots';

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('Invoice document snapshots are immutable.');
        });
        self::deleting(static function (): void {
            throw new LogicException('Invoice document snapshots are retained with their invoice.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'document_kind' => InvoiceDocumentKind::class,
            'organization_profile_present' => 'boolean',
            'counterparty_present' => 'boolean',
            'supply_date' => 'date',
            'supply_period_start' => 'date',
            'supply_period_end' => 'date',
            'purchaser_reference_fields' => 'array',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
