<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Models\Concerns\HasPurchaseRowVersion;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;

final class PurchaseDebitNote extends TenantOwnedModel
{
    use HasPurchaseRowVersion;
    use SoftDeletes;

    protected $table = 'purchase_debit_notes';

    protected static function booted(): void
    {
        self::updating(static function (self $document): void {
            if ($document->isDirty('currency_id')) {
                throw new LogicException('Purchase financial document currency cannot change after creation.');
            }

            $rawOriginalStatus = $document->getRawOriginal('status');
            $originalStatus = $rawOriginalStatus instanceof PurchaseDebitNoteStatus
                ? $rawOriginalStatus
                : ($rawOriginalStatus !== null ? PurchaseDebitNoteStatus::tryFrom((string) $rawOriginalStatus) : null);

            if ($originalStatus !== null && $originalStatus !== PurchaseDebitNoteStatus::Draft) {
                $immutable = [
                    'debit_note_number',
                    'debit_note_date',
                    'supplier_id',
                    'purchase_return_id',
                    'amount',
                    'tenant_id',
                    'organization_unit_id',
                ];
                if ($document->isDirty($immutable)) {
                    throw new LogicException('Non-draft purchase debit notes are immutable in place.');
                }
            }
        });

        self::deleting(static function (self $document): void {
            $status = $document->status instanceof PurchaseDebitNoteStatus
                ? $document->status
                : PurchaseDebitNoteStatus::from((string) $document->status);

            if ($status !== PurchaseDebitNoteStatus::Draft) {
                throw new LogicException('Only draft purchase debit notes can be deleted.');
            }
        });
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'currency_id' => 'integer',
            'purchase_return_id' => 'integer',
            'source_id' => 'integer',
            'debit_note_date' => 'date',
            'status' => PurchaseDebitNoteStatus::class,
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'approved_at' => 'datetime',
        ]);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }
}
