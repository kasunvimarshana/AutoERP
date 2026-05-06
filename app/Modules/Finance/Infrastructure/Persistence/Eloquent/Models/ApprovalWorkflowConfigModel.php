<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ApprovalWorkflowConfigModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'approval_workflow_configs';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'module', 'entity_type', 'name',
        'min_amount', 'max_amount', 'steps', 'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'min_amount' => 'decimal:6',
        'max_amount' => 'decimal:6',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
