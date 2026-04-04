<?php
namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class CustomerAddressModel extends BaseModel
{
    protected $table = 'customer_addresses';

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
