<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;
use Modules\Core\Models\CoreModel;
use Modules\Finance\Enums\JournalStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceJournalLine extends CoreModel
{
    protected $table = 'finance_journal_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'journal_entry_id' => 'integer',
            'account_id' => 'integer',
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'dimension_id' => 'integer',
            'source_line_id' => 'integer',
            'line_number' => 'integer',
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

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(FinanceDimension::class, 'dimension_id');
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(FinanceLedgerEntry::class, 'journal_line_id');
    }

    protected static function booted(): void
    {
        $assertDraft = static function (self $line): void {
            $journal = $line->journalEntry()->first();
            if (! $journal instanceof FinanceJournalEntry) {
                return;
            }

            $status = $journal->status instanceof JournalStatus
                ? $journal->status
                : JournalStatus::from((string) $journal->status);

            if ($status !== JournalStatus::Draft) {
                throw new LogicException('Posted journal lines are immutable.');
            }
        };

        self::updating($assertDraft);
        self::deleting($assertDraft);
    }
}
