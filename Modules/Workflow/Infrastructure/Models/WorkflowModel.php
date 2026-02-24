<?php

namespace Modules\Workflow\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class WorkflowModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'workflows';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'description',
        'document_type',
        'states',
        'transitions',
        'is_active',
    ];

    protected $casts = [
        'states'      => 'array',
        'transitions' => 'array',
        'is_active'   => 'boolean',
    ];

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
