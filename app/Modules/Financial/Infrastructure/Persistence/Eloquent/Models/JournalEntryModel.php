<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class JournalEntryModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'entry_number',
        'entry_date',
        'posting_date',
        'type',
        'status',
        'description',
        'reference',
        'currency_code',
        'exchange_rate',
        'total_debit',
        'total_credit',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'metadata',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'total_debit'   => 'decimal:4',
        'total_credit'  => 'decimal:4',
        'entry_date'    => 'date',
        'posting_date'  => 'date',
        'posted_at'     => 'datetime',
        'voided_at'     => 'datetime',
        'metadata'      => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /**
     * The journal entry lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'journal_entry_id');
    }

    /**
     * The fiscal year this entry belongs to.
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYearModel::class, 'fiscal_year_id');
    }
}
