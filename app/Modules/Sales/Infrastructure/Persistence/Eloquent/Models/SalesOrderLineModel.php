<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalesOrderLineModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'sales_order_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}
