<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WorkflowInstanceModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'workflow_instances';

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'current_state_id',
        'status',
        'started_at',
        'completed_at',
        'started_by_user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
