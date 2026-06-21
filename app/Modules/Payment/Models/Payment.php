<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
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
            'source_id' => 'integer',
            'currency_id' => 'integer',
            'bank_account_id' => 'integer',
            'finance_journal_entry_id' => 'integer',
            'reversal_payment_id' => 'integer',
            'original_payment_id' => 'integer',
            'payment_type' => PaymentType::class,
            'direction' => PaymentDirection::class,
            'document_status' => PaymentDocumentStatus::class,
            'allocation_status' => PaymentAllocationState::class,
            'posting_status' => PaymentPostingStatus::class,
            'instrument_status' => PaymentInstrumentStatus::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'cheque_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'unapplied_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'metadata' => 'array',
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'voided_by' => 'integer',
            'voided_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }

    protected static function booted(): void
    {
        self::deleting(function (Payment $payment): void {
            $status = $payment->status instanceof PaymentStatus
                ? $payment->status
                : PaymentStatus::from((string) $payment->status);

            if (! in_array($status, [PaymentStatus::Draft, PaymentStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Posted, allocated, refunded, or reversed payments cannot be deleted.');
            }
        });
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'bank_account_id');
    }

    public function financeJournalEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'finance_journal_entry_id');
    }

    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_payment_id');
    }

    public function reversalPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_payment_id');
    }

    public function refundPayments(): HasMany
    {
        return $this->hasMany(self::class, 'original_payment_id');
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

    public function chequePrintLogs(): HasMany
    {
        return $this->hasMany(ChequePrintLog::class, 'payment_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(PaymentStatusHistory::class, 'payment_id');
    }
}
