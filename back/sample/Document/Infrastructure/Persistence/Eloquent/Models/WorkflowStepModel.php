<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStepModel extends Model
{
    protected $table = 'document_workflow_steps';

    protected $fillable = [
        'workflow_id',
        'sequence',
        'name',
        'display_name',
        'is_initial',
        'is_terminal',
    ];

    public function outgoingTransitions()
    {
        return $this->hasMany(WorkflowTransitionModel::class, 'from_step_id');
    }
}
