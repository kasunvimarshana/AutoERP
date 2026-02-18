<?php

declare(strict_types=1);

namespace Modules\HR\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\HR\Database\Factories\PerformanceReviewFactory;

/**
 * PerformanceReview Entity
 *
 * Represents a performance evaluation for an employee.
 * Tracks ratings, goals, feedback, and review periods.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property int $reviewer_id
 * @property string $review_period_start
 * @property string $review_period_end
 * @property int $overall_rating
 * @property string|null $strengths
 * @property string|null $areas_for_improvement
 * @property string|null $goals
 * @property string|null $achievements
 * @property string|null $comments
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PerformanceReview extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'reviewer_id',
        'review_period_start',
        'review_period_end',
        'overall_rating',
        'strengths',
        'areas_for_improvement',
        'goals',
        'achievements',
        'comments',
        'status',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'overall_rating' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PerformanceReviewFactory
    {
        return PerformanceReviewFactory::new();
    }

    /**
     * Get the employee being reviewed.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    /**
     * Check if the review is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->completed_at !== null;
    }

    /**
     * Check if the review is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
