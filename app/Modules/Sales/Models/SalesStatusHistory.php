<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Modules\Core\Models\CoreModel;

final class SalesStatusHistory extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'changed_at' => 'datetime',
        ]);
    }
}
