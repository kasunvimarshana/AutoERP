<?php

namespace App\Modules\ReportingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Report extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ReportFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'report_name',
        'report_type',
        'description',
        'parameters',
        'data',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'generated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->uuid)) {
                $report->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the report
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the user who generated the report
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }

    /**
     * Get report type label
     */
    public function getTypeLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->report_type));
    }

    /**
     * Check if report has cached data
     */
    public function hasCachedData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By report type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope: Generated in date range
     */
    public function scopeGeneratedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('generated_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Recent reports
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }
}
