<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * FiscalPeriod entity.
 *
 * Represents a fiscal period (e.g., monthly or annual) within a tenant.
 * Journal entries are always associated with a fiscal period.
 */
class FiscalPeriod extends Model
{
    use HasTenant;

    protected $table = 'fiscal_periods';

    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_closed'  => 'boolean',
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'fiscal_period_id');
    }
}
