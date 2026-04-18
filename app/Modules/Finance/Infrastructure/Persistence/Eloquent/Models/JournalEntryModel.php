<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class JournalEntryModel extends Model
{
    use HasAudit;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
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

    public function lines()
    {
        return $this->hasMany(\Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel::class, 'journal_entry_id');
    }
}
