<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class Gs1IdentifierModel extends BaseModel
{
    protected $table = 'gs1_identifiers';

    protected $fillable = [
        'tenant_id',
        'identifier_type',
        'identifier_value',
        'entity_type',
        'entity_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'entity_id' => 'integer',
        'is_active' => 'boolean',
        'metadata'  => 'array',
    ];
}
