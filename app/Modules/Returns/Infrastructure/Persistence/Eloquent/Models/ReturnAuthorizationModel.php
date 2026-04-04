<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ReturnAuthorizationModel extends BaseModel
{
    protected $table = 'return_authorizations';

    protected $casts = [
        'expires_at'  => 'datetime',
        'approved_at' => 'datetime',
    ];
}
