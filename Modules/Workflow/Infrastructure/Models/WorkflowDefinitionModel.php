<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WorkflowDefinitionModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'workflow_definitions';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'entity_type',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
