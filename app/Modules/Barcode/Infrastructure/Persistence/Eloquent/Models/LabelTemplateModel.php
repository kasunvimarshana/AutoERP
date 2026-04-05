<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class LabelTemplateModel extends BaseModel
{
    protected $table = 'label_templates';

    protected $fillable = [
        'tenant_id', 'name', 'format', 'content', 'default_variables', 'is_active',
    ];

    protected $casts = [
        'id'                => 'int',
        'tenant_id'         => 'int',
        'default_variables' => 'array',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];
}
