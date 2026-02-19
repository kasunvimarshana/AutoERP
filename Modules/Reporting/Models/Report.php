<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Reporting\Enums\ReportFormat;
use Modules\Reporting\Enums\ReportStatus;
use Modules\Reporting\Enums\ReportType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Report Model
 *
 * Represents a report definition with query configuration
 */
class Report extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'user_id',
        'name',
        'description',
        'type',
        'format',
        'status',
        'query_config',
        'fields',
        'filters',
        'grouping',
        'sorting',
        'aggregations',
        'metadata',
        'is_template',
        'is_shared',
        'published_at',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'format' => ReportFormat::class,
        'status' => ReportStatus::class,
        'query_config' => 'array',
        'fields' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'aggregations' => 'array',
        'metadata' => 'array',
        'is_template' => 'boolean',
        'is_shared' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user who created this report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get saved reports based on this report
     */
    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class);
    }

    /**
     * Get schedules for this report
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    /**
     * Get executions of this report
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    /**
     * Check if report is published
     */
    public function isPublished(): bool
    {
        return $this->status === ReportStatus::PUBLISHED;
    }

    /**
     * Check if report is a template
     */
    public function isTemplate(): bool
    {
        return $this->is_template;
    }

    /**
     * Publish the report
     */
    public function publish(): void
    {
        $this->update([
            'status' => ReportStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Archive the report
     */
    public function archive(): void
    {
        $this->update([
            'status' => ReportStatus::ARCHIVED,
        ]);
    }
}
