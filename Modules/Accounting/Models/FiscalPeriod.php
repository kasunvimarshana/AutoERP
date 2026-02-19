<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\FiscalPeriodStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * Fiscal Period Model
 *
 * Represents a fiscal/accounting period (typically monthly) within a fiscal year
 */
class FiscalPeriod extends Model
{
    use HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'fiscal_year_id',
        'name',
        'code',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => FiscalPeriodStatus::class,
        'closed_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Organization relationship
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Fiscal year relationship
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Journal entries relationship
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Check if period is open
     */
    public function isOpen(): bool
    {
        return $this->status === FiscalPeriodStatus::Open;
    }

    /**
     * Check if period is closed
     */
    public function isClosed(): bool
    {
        return $this->status === FiscalPeriodStatus::Closed;
    }

    /**
     * Check if period is locked
     */
    public function isLocked(): bool
    {
        return $this->status === FiscalPeriodStatus::Locked;
    }
}
