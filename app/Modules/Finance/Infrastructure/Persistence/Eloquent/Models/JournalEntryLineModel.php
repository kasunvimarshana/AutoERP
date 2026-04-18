<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLineModel extends Model
{
    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'currency_id',
        'exchange_rate',
        'base_debit_amount',
        'base_credit_amount',
        'cost_center_id',
        'metadata',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'base_debit_amount' => 'decimal:4',
        'base_credit_amount' => 'decimal:4',
        'metadata' => 'array',
    ];
}
