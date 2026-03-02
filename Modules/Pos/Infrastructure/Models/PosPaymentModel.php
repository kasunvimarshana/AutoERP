<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class PosPaymentModel extends Model
{
    use BelongsToTenant;

    protected $table = 'pos_payments';

    protected $fillable = [
        'tenant_id',
        'pos_order_id',
        'method',
        'amount',
        'currency',
        'reference',
    ];
}
