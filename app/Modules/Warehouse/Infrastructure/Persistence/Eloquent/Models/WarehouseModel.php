<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class WarehouseModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}