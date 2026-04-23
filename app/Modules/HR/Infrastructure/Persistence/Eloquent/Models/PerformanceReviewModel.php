<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class PerformanceReviewModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_performance_reviews';

    protected $fillable = ['tenant_id', 'employee_id', 'cycle_id', 'reviewer_id', 'overall_rating', 'goals', 'strengths', 'improvements', 'reviewer_comments', 'employee_comments', 'status', 'acknowledged_at', 'metadata'];

    protected $casts = ['goals' => 'array', 'metadata' => 'array', 'acknowledged_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycleModel::class, 'cycle_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reviewer_id');
    }
}
