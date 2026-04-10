<?php

namespace App\Modules\Returns\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReturnOrder extends BaseModel
{
    protected $table = 'return_orders';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'return_number',
        'direction',
        'party_id',
        'original_order_type',
        'original_order_id',
        'warehouse_id',
        'return_date',
        'reason',
        'status',
        'restock_action',
        'restocking_fee',
        'total_amount',
        'credit_note_id',
        'journal_entry_id',
        'notes',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'restocking_fee' => 'decimal:4',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class, 'warehouse_id');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Returns\Models\CreditNote::class, 'credit_note_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }
}
