<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WorkflowDefinition entity.
 *
 * Represents a workflow definition for a specific entity type (e.g. sales_order).
 * A tenant may define multiple workflows for different entity types.
 */
class WorkflowDefinition extends Model
{
    use HasTenant;

    protected $table = 'workflow_definitions';

    protected $fillable = [
        'tenant_id',
        'name',
        'entity_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(WorkflowState::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class);
    }
}
