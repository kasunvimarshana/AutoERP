<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class PerformanceReviewModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_performance_reviews';

    protected $fillable = [
        'tenant_id', 'employee_id', 'reviewer_id', 'review_period_start', 'review_period_end',
        'rating', 'comments', 'goals', 'achievements', 'status', 'metadata',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'employee_id' => 'integer',
        'reviewer_id' => 'integer',
        'rating'      => 'float',
        'metadata'    => 'array',
    ];
}
