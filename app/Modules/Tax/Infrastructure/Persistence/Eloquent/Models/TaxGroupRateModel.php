<?php
declare(strict_types=1);
namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxGroupRateModel extends BaseModel
{
    protected $table = 'tax_group_rates';
    protected $fillable = [
        'tenant_id', 'tax_group_id', 'tax_rate_code', 'tax_rate_name',
        'rate', 'sort_order', 'is_compound',
    ];
    protected $casts = [
        'id'           => 'int',
        'tenant_id'    => 'int',
        'tax_group_id' => 'int',
        'rate'         => 'float',
        'sort_order'   => 'int',
        'is_compound'  => 'bool',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];
}
