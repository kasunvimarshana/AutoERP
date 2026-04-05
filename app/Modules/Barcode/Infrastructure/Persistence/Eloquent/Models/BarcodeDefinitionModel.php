<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BarcodeDefinitionModel extends BaseModel
{
    protected $table = 'barcode_definitions';

    protected $fillable = [
        'tenant_id', 'type', 'value', 'label',
        'entity_type', 'entity_id', 'metadata', 'is_active',
    ];

    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'entity_id'  => 'int',
        'metadata'   => 'array',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
