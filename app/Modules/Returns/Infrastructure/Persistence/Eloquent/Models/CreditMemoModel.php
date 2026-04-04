<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class CreditMemoModel extends BaseModel
{
    protected $table = 'credit_memos';

    protected $casts = [
        'amount'    => 'float',
        'issued_at' => 'datetime',
    ];
}
