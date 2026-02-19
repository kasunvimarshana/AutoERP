<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Reporting\Enums\ScheduleFrequency;
use Modules\Tenant\Traits\TenantScoped;

/**
 * ReportSchedule Model
 *
 * Represents scheduled report execution configuration
 */
class ReportSchedule extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'report_id',
        'name',
        'frequency',
        'cron_expression',
        'parameters',
        'recipients',
        'export_formats',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'frequency' => ScheduleFrequency::class,
        'parameters' => 'array',
        'recipients' => 'array',
        'export_formats' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the report to be scheduled
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get execution history
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class, 'schedule_id');
    }

    /**
     * Activate the schedule
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the schedule
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Update last run timestamp
     */
    public function updateLastRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Calculate next run time based on frequency
     */
    public function calculateNextRun(): \DateTime
    {
        $now = now();

        return match ($this->frequency) {
            ScheduleFrequency::DAILY => $now->addDay(),
            ScheduleFrequency::WEEKLY => $now->addWeek(),
            ScheduleFrequency::MONTHLY => $now->addMonth(),
            ScheduleFrequency::QUARTERLY => $now->addMonths(3),
            ScheduleFrequency::YEARLY => $now->addYear(),
        };
    }
}
