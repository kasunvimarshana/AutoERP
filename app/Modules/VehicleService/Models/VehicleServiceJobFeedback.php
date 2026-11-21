<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\UserModel;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;
use Modules\VehicleService\Models\Concerns\HasVehicleServiceJobRowVersion;

final class VehicleServiceJobFeedback extends TenantOwnedModel
{
    use HasVehicleServiceJobRowVersion;
    use SoftDeletes;

    protected $table = 'vehicle_service_job_feedbacks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'user_id' => 'integer',
            'feedback_type' => VehicleServiceFeedbackType::class,
            'rating' => 'integer',
            'is_customer_visible' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where($this->qualifyColumn('tenant_id'), $tenantId);

        if ($organizationUnitId !== null) {
            $query->where($this->qualifyColumn('organization_unit_id'), $organizationUnitId);
        }

        return $query;
    }

    public function scopeCustomerVisible(Builder $query): Builder
    {
        return $query->where('is_customer_visible', true);
    }

    public function scopeReviews(Builder $query): Builder
    {
        return $query->where('feedback_type', VehicleServiceFeedbackType::Review);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'updated_by');
    }
}
