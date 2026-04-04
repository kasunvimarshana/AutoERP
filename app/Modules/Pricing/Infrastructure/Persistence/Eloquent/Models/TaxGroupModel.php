<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxGroupModel extends BaseModel
{
    protected $table = 'tax_groups';

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
