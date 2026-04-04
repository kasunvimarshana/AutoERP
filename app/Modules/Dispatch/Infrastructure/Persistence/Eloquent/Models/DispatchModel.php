<?php

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class DispatchModel extends BaseModel
{
    protected $table = 'dispatches';

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at'  => 'datetime',
    ];
}
