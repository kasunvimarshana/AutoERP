<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLineModel extends Model
{
    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'currency',
        'exchange_rate',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'id'               => 'integer',
        'journal_entry_id' => 'integer',
        'account_id'       => 'integer',
        'debit_amount'     => 'float',
        'credit_amount'    => 'float',
        'exchange_rate'    => 'float',
        'sort_order'       => 'integer',
        'metadata'         => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }
}
