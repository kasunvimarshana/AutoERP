<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class JournalEntryLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'journal_entry_lines';

    // No soft deletes on lines — they are deleted with the parent entry
    public $timestamps = true;

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'currency_code',
        'exchange_rate',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'debit'         => 'decimal:4',
        'credit'        => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'metadata'      => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /**
     * The parent journal entry header.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    /**
     * The account this line is posted to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }
}
