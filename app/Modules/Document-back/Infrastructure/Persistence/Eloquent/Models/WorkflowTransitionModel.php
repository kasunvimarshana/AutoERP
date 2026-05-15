<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTransitionModel extends Model
{
    protected $table = 'document_workflow_transitions';

    protected $fillable = [
        'from_step_id',
        'to_step_id',
        'action_name',
        'condition_expression',
        'required_ability',
    ];
}
