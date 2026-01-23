<?php

namespace App\Modules\JobCardManagement\Models;

use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DigitalInspection extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'job_card_id',
        'vehicle_id',
        'inspector_id',
        'inspection_type',
        'inspection_data',
        'photos',
        'overall_notes',
        'overall_status',
        'inspected_at',
    ];

    protected $casts = [
        'inspection_data' => 'array',
        'photos' => 'array',
        'inspected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inspection) {
            if (empty($inspection->uuid)) {
                $inspection->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the job card that owns the inspection
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Get the vehicle being inspected
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the inspector (user)
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'inspector_id');
    }

    /**
     * Check if inspection is pre-service
     */
    public function isPreService(): bool
    {
        return $this->inspection_type === 'pre_service';
    }

    /**
     * Check if inspection is post-service
     */
    public function isPostService(): bool
    {
        return $this->inspection_type === 'post_service';
    }

    /**
     * Check if inspection is detailed
     */
    public function isDetailed(): bool
    {
        return $this->inspection_type === 'detailed';
    }

    /**
     * Check if overall status is excellent
     */
    public function isExcellent(): bool
    {
        return $this->overall_status === 'excellent';
    }

    /**
     * Check if overall status is good
     */
    public function isGood(): bool
    {
        return $this->overall_status === 'good';
    }

    /**
     * Check if overall status is fair
     */
    public function isFair(): bool
    {
        return $this->overall_status === 'fair';
    }

    /**
     * Check if overall status is poor
     */
    public function isPoor(): bool
    {
        return $this->overall_status === 'poor';
    }

    /**
     * Get total number of photos
     */
    public function getPhotoCountAttribute(): int
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    /**
     * Check if inspection has photos
     */
    public function hasPhotos(): bool
    {
        return $this->photo_count > 0;
    }

    /**
     * Get inspection items count
     */
    public function getInspectionItemsCountAttribute(): int
    {
        return is_array($this->inspection_data) ? count($this->inspection_data) : 0;
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
     * Scope: By inspection type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('inspection_type', $type);
    }

    /**
     * Scope: By overall status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('overall_status', $status);
    }

    /**
     * Scope: By inspector
     */
    public function scopeByInspector($query, int $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    /**
     * Scope: Recent inspections
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('inspected_at', '>=', now()->subDays($days));
    }
}
