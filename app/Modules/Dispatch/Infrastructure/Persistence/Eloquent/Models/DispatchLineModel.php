<?php

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class DispatchLineModel extends BaseModel
{
    protected $table = 'dispatch_lines';

    protected $casts = [
        'dispatched_qty' => 'float',
    ];
}
