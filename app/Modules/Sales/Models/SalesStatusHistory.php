<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Modules\Core\Models\TenantOwnedModel;

final class SalesStatusHistory extends TenantOwnedModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'changed_at' => 'datetime',
        ]);
    }
}
