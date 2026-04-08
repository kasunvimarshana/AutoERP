<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class BankTransactionModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'bank_transactions';

    protected $fillable = [
        'tenant_id',
        'bank_account_id',
        'transaction_date',
        'value_date',
        'description',
        'reference',
        'type',
        'amount',
        'balance',
        'currency_code',
        'status',
        'journal_entry_id',
        'metadata',
    ];

    protected $casts = [
        'amount'           => 'decimal:4',
        'balance'          => 'decimal:4',
        'transaction_date' => 'date',
        'value_date'       => 'date',
        'metadata'         => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    /**
     * The bank account this transaction belongs to.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccountModel::class, 'bank_account_id');
    }

    /**
     * The journal entry generated for this transaction (if reconciled).
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }
}
