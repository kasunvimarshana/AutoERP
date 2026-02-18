<?php

namespace App\Modules\Reporting\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Enums\ReportType;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Report Model
 *
 * Represents a saved report definition with query configuration.
 */
class Report extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Reporting\ReportFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'report_type',
        'module',
        'query_config',
        'schedule_config',
        'is_public',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_type' => ReportType::class,
        'query_config' => 'array',
        'schedule_config' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the tenant that owns the report
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the report
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the executions for the report
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    /**
     * Get the scheduled reports for this report
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ScheduledReport::class);
    }

    /**
     * Check if report is public
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Get the last execution
     */
    public function getLastExecution(): ?ReportExecution
    {
        return $this->executions()->latest()->first();
    }
}
