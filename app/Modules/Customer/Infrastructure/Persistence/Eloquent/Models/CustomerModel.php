<?php
namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';

    protected $casts = [
        'credit_limit' => 'float',
    ];
}
