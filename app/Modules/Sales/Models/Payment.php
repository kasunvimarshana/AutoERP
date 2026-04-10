<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends BaseModel
{
    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'payment_number',
        'direction',
        'party_id',
        'bank_account_id',
        'payment_date',
        'method',
        'currency_id',
        'exchange_rate',
        'amount',
        'reference',
        'notes',
        'status',
        'journal_entry_id',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'amount' => 'decimal:4',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\BankAccount::class, 'bank_account_id');
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
