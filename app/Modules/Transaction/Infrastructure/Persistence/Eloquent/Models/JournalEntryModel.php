<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class JournalEntryModel extends BaseModel
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'account_code',
        'account_name',
        'debit_amount',
        'credit_amount',
        'description',
        'status',
        'posted_at',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'      => 'integer',
        'transaction_id' => 'integer',
        'debit_amount'   => 'float',
        'credit_amount'  => 'float',
        'posted_at'      => 'datetime',
        'metadata'       => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionModel::class, 'transaction_id');
    }
}
