<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}