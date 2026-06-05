<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'year' => 'integer',
            'row_version' => 'integer',
        ];
    }
}
