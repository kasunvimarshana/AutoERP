<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentWorkflowModel extends Model
{
    protected $table = 'document_workflows';

    protected $fillable = [
        'tenant_id',
        'document_type_id',
        'name',
        'is_default',
        'is_active',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowStepModel::class, 'workflow_id')->orderBy('sequence');
    }
}
