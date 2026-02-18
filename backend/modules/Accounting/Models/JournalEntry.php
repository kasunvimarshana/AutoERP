<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

/**
 * Journal Entry Model
 *
 * Represents a journal entry for double-entry bookkeeping.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $entry_number
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string $reference
 * @property string|null $description
 * @property float $total_debit
 * @property float $total_credit
 * @property string $currency_code
 * @property bool $is_posted
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property string|null $posted_by
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class JournalEntry extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'total_debit',
        'total_credit',
        'currency_code',
        'is_posted',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    /**
     * Get the journal entry lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}
