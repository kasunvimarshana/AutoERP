<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WorkflowState entity.
 *
 * Represents a single state within a workflow definition.
 */
class WorkflowState extends Model
{
    use HasTenant;

    protected $table = 'workflow_states';

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'name',
        'label',
        'is_initial',
        'is_final',
        'sort_order',
    ];

    protected $casts = [
        'is_initial'  => 'boolean',
        'is_final'    => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
