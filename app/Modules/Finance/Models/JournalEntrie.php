<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntrie extends BaseModel
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'entry_number',
        'entry_date',
        'post_date',
        'source_type',
        'source_id',
        'reference',
        'description',
        'currency_id',
        'exchange_rate',
        'status',
        'reversed_by',
        'created_by',
        'posted_by',
        'created_at',
        'posted_at'
    ];

    protected $casts = [
        'post_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'created_at' => 'datetime',
        'posted_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }
}
