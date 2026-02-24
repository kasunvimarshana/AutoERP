<?php

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkflowHistoryModel extends Model
{
    protected $table = 'workflow_histories';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'workflow_id',
        'document_type',
        'document_id',
        'from_state',
        'to_state',
        'actor_id',
        'comment',
    ];

    public $timestamps = true;
    const UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
