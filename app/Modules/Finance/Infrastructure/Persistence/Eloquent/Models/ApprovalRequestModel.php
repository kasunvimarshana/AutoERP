<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class ApprovalRequestModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'approval_requests';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'workflow_config_id', 'entity_type',
        'entity_id', 'status', 'current_step_order', 'requested_by_user_id',
        'resolved_by_user_id', 'requested_at', 'resolved_at', 'comments',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
        'current_step_order' => 'integer',
        'row_version' => 'integer',
    ];

    public function workflowConfig(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowConfigModel::class, 'workflow_config_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'requested_by_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'resolved_by_user_id');
    }
}
