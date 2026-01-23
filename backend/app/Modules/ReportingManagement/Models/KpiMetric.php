<?php

namespace App\Modules\ReportingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KpiMetric extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\KpiMetricFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'metric_name',
        'metric_type',
        'metric_value',
        'unit',
        'period_start',
        'period_end',
        'period_type',
        'breakdown',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'breakdown' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($metric) {
            if (empty($metric->uuid)) {
                $metric->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the metric
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get metric name label
     */
    public function getNameLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->metric_name));
    }

    /**
     * Get metric type label
     */
    public function getTypeLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->metric_type));
    }

    /**
     * Get formatted metric value
     */
    public function getFormattedValue(): string
    {
        return match ($this->unit) {
            'currency' => '$' . number_format($this->metric_value, 2),
            'percentage' => number_format($this->metric_value, 2) . '%',
            'count' => number_format($this->metric_value, 0),
            default => number_format($this->metric_value, 2),
        };
    }

    /**
     * Check if metric has breakdown data
     */
    public function hasBreakdown(): bool
    {
        return !empty($this->breakdown);
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
     * Scope: By metric name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('metric_name', $name);
    }

    /**
     * Scope: By metric type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope: By period type
     */
    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope: For date range
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('period_start', [$startDate, $endDate])
                ->orWhereBetween('period_end', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('period_start', '<=', $startDate)
                        ->where('period_end', '>=', $endDate);
                });
        });
    }

    /**
     * Scope: Recent metrics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('period_end', '>=', now()->subDays($days));
    }
}
