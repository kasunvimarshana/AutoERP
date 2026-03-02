<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WorkflowTransitionModel extends Model
{
    use BelongsToTenant;

    protected $table = 'workflow_transitions';

    protected $fillable = [
        'workflow_definition_id',
        'from_state_id',
        'to_state_id',
        'tenant_id',
        'name',
        'description',
        'requires_comment',
    ];

    protected $casts = [
        'requires_comment' => 'boolean',
    ];
}
