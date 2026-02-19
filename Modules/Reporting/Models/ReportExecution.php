<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;

/**
 * ReportExecution Model
 *
 * Log of report runs and their results
 */
class ReportExecution extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'report_id',
        'schedule_id',
        'user_id',
        'parameters',
        'filters',
        'result_count',
        'execution_time',
        'started_at',
        'completed_at',
        'failed_at',
        'error_message',
        'export_path',
        'export_format',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters' => 'array',
        'result_count' => 'integer',
        'execution_time' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the report that was executed
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the schedule that triggered this execution
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'schedule_id');
    }

    /**
     * Get the user who executed the report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark execution as completed
     */
    public function markAsCompleted(int $resultCount, float $executionTime): void
    {
        $this->update([
            'result_count' => $resultCount,
            'execution_time' => $executionTime,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark execution as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->completed_at !== null && $this->failed_at === null;
    }

    /**
     * Check if execution failed
     */
    public function hasFailed(): bool
    {
        return $this->failed_at !== null;
    }
}
