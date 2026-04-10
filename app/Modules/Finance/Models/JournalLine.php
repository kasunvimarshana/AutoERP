<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalLine extends BaseModel
{
    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'base_debit',
        'base_credit',
        'party_id',
        'cost_center_id',
        'description',
        'line_number'
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'base_debit' => 'decimal:4',
        'base_credit' => 'decimal:4',
        'line_number' => 'integer'
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'party_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\CostCenter::class, 'cost_center_id');
    }
}
