<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WorkflowInstanceLogModel extends Model
{
    use BelongsToTenant;

    protected $table = 'workflow_instance_logs';

    public $timestamps = false;

    protected $fillable = [
        'workflow_instance_id',
        'tenant_id',
        'from_state_id',
        'to_state_id',
        'transition_id',
        'comment',
        'actor_user_id',
        'acted_at',
        'created_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
