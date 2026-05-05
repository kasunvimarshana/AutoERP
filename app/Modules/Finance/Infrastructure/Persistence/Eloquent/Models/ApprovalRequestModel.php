<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

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
}
