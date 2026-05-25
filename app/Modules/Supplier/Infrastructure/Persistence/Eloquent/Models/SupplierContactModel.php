<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierContactModel extends CoreModel
{


    protected $table = 'supplier_contacts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}