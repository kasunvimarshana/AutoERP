<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class JournalEntryModel extends BaseModel
{
    use HasAudit, SoftDeletes;
    use HasTenant;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'fiscal_period_id',
        'entry_number',
        'entry_type',
        'reference_type',
        'reference_id',
        'description',
        'entry_date',
        'posting_date',
        'status',
        'is_reversed',
        'reversal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posting_date' => 'date',
        'posted_at' => 'datetime',
        'is_reversed' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'journal_entry_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriodModel::class, 'fiscal_period_id');
    }
}
