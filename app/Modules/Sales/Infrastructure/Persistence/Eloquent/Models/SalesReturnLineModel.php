<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalesReturnLineModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'sales_return_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}
