<?php

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class OrganizationUnitModel extends BaseModel
{
    protected $table = 'organization_units';

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
