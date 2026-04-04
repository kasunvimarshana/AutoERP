<?php
namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SupplierContactModel extends BaseModel
{
    protected $table = 'supplier_contacts';

    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
