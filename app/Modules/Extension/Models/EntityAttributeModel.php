<?php

declare(strict_types=1);

namespace Modules\Extension\Models;

use Modules\Core\Models\CoreModel;

final class EntityAttributeModel extends CoreModel
{
    protected $table = 'entity_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'entity_id' => 'integer',
        ]);
    }
}
