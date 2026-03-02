<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WorkflowStateModel extends Model
{
    use BelongsToTenant;

    protected $table = 'workflow_states';

    protected $fillable = [
        'workflow_definition_id',
        'tenant_id',
        'name',
        'description',
        'is_initial',
        'is_final',
        'sort_order',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'sort_order' => 'integer',
    ];
}
