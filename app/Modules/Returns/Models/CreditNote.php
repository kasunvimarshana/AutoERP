<?php

namespace App\Modules\Returns\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CreditNote extends BaseModel
{
    protected $table = 'credit_notes';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'cn_number',
        'direction',
        'party_id',
        'return_order_id',
        'issue_date',
        'currency_id',
        'amount',
        'remaining_amount',
        'status',
        'journal_entry_id',
        'notes',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'party_id');
    }

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Returns\Models\ReturnOrder::class, 'return_order_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }
}
