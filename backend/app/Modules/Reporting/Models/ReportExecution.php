<?php

namespace App\Modules\Reporting\Models;

use App\Models\User;
use App\Modules\Reporting\Enums\ReportExecutionStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReportExecution Model
 *
 * Represents a report execution history.
 */
class ReportExecution extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Reporting\ReportExecutionFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'report_id',
        'executed_by_id',
        'execution_time',
        'parameters',
        'result_count',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'execution_time' => 'float',
        'parameters' => 'array',
        'result_count' => 'integer',
        'status' => ReportExecutionStatus::class,
    ];

    /**
     * Get the report
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the user who executed the report
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_id');
    }

    /**
     * Check if execution is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === ReportExecutionStatus::COMPLETED;
    }

    /**
     * Check if execution failed
     */
    public function isFailed(): bool
    {
        return $this->status === ReportExecutionStatus::FAILED;
    }

    /**
     * Check if execution is running
     */
    public function isRunning(): bool
    {
        return $this->status === ReportExecutionStatus::RUNNING;
    }
}
