<?php

namespace App\Modules\Reporting\Models;

use App\Modules\Reporting\Enums\ExportFormat;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ScheduledReport Model
 *
 * Represents a scheduled report with cron configuration.
 */
class ScheduledReport extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Reporting\ScheduledReportFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'report_id',
        'schedule_cron',
        'recipients',
        'format',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recipients' => 'array',
        'format' => ExportFormat::class,
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the report
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Check if schedule is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if schedule is due
     */
    public function isDue(): bool
    {
        return $this->is_active &&
               $this->next_run_at &&
               $this->next_run_at->isPast();
    }
}
