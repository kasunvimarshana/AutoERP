<?php
declare(strict_types=1);
namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ExchangeRateModel extends BaseModel
{
    protected $table = 'exchange_rates';
    protected $fillable = [
        'tenant_id', 'from_currency', 'to_currency', 'rate', 'source',
        'valid_from', 'valid_to',
    ];
    protected $casts = [
        'id'            => 'int',
        'tenant_id'     => 'int',
        'rate'          => 'float',
        'valid_from'    => 'datetime',
        'valid_to'      => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];
}
