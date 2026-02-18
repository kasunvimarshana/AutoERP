<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\FiscalPeriodFactory;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;

/**
 * Fiscal Period Entity
 *
 * Represents an accounting period (year, quarter, month).
 * Controls when journal entries can be posted.
 */
class FiscalPeriod extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'period_type',
        'fiscal_year',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Period type constants.
     */
    public const TYPE_YEAR = 'year';

    public const TYPE_QUARTER = 'quarter';

    public const TYPE_MONTH = 'month';

    /**
     * Status constants.
     */
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LOCKED = 'locked';

    /**
     * Get journal entries for this period.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Get the user who closed this period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function closedBy()
    {
        return $this->belongsTo('App\Models\User', 'closed_by');
    }

    /**
     * Check if period is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if period is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    /**
     * Check if date falls within this period.
     */
    public function containsDate(\DateTimeInterface $date): bool
    {
        $checkDate = \Carbon\Carbon::parse($date);

        return $checkDate->between($this->start_date, $this->end_date);
    }

    /**
     * Check if period can accept new entries.
     */
    public function canAcceptEntries(): bool
    {
        return $this->isOpen() && now()->between($this->start_date, $this->end_date);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FiscalPeriodFactory
    {
        return FiscalPeriodFactory::new();
    }
}
