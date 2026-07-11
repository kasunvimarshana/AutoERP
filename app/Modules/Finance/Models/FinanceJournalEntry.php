<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceJournalEntry extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'finance_journal_entries';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'posting_profile_id' => 'integer',
            'source_id' => 'integer',
            'source_date' => 'date',
            'journal_type' => JournalType::class,
            'status' => JournalStatus::class,
            'journal_date' => 'date',
            'total_debit' => 'decimal:6',
            'total_credit' => 'decimal:6',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:6',
            'created_by' => 'integer',
            'posted_by' => 'integer',
            'posted_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
            'reversal_of_id' => 'integer',
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

    public function postingProfile(): BelongsTo
    {
        return $this->belongsTo(FinancePostingProfile::class, 'posting_profile_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceJournalLine::class, 'journal_entry_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinanceLedgerEntry::class, 'journal_entry_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    protected static function booted(): void
    {
        self::updating(function (self $journal): void {
            $originalStatus = $journal->getOriginal('status');
            $status = $originalStatus instanceof JournalStatus
                ? $originalStatus
                : JournalStatus::from((string) $originalStatus);

            if ($status === JournalStatus::Posted) {
                $allowed = ['status', 'reversed_by', 'reversed_at', 'reversal_reason', 'updated_at'];
                if (array_diff(array_keys($journal->getDirty()), $allowed) !== []) {
                    throw new LogicException('Posted journals are immutable and must be corrected by reversal.');
                }
            }

            if ($status === JournalStatus::Reversed) {
                throw new LogicException('Reversed journals are immutable.');
            }
        });

        self::deleting(function (self $journal): void {
            $status = $journal->status instanceof JournalStatus
                ? $journal->status
                : JournalStatus::from((string) $journal->status);

            if (in_array($status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
                throw new LogicException('Posted journals cannot be deleted.');
            }
        });
    }
}
