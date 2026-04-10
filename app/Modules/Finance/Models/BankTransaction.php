<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankTransaction extends BaseModel
{
    protected $table = 'bank_transactions';

    protected $fillable = [
        'bank_account_id',
        'transaction_date',
        'amount',
        'description',
        'reference',
        'type',
        'status',
        'journal_entry_id',
        'category_rule_id',
        'source',
        'raw_data',
        'imported_at'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:4',
        'raw_data' => 'array',
        'imported_at' => 'datetime'
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\BankAccount::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }
}
