<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Models\FinanceAccount;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class TaxTransaction extends TenantOwnedModel
{
    protected $table = 'tax_transactions';

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('TaxTransaction is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('TaxTransaction is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'tax_id' => 'integer',
            'tax_document_snapshot_id' => 'integer',
            'source_id' => 'integer',
            'party_id' => 'integer',
            'taxable_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'withholding_amount' => 'decimal:6',
            'is_withholding' => 'boolean',
            'recoverable' => 'boolean',
            'payable' => 'boolean',
            'receivable' => 'boolean',
            'account_id' => 'integer',
            'transaction_date' => 'date',
            'metadata' => 'array',
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

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(TaxDocumentSnapshot::class, 'tax_document_snapshot_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
