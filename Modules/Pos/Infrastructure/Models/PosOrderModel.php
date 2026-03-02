<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class PosOrderModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'pos_orders';

    protected $fillable = [
        'tenant_id',
        'pos_session_id',
        'reference',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'notes',
    ];
}
