<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PosOrderPaymentModel extends Model
{
    use HasUuids, HasTenantScope;

    protected $table = 'pos_order_payments';

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'order_id',
        'payment_method',
        'amount',
        'reference',
    ];

    protected $casts = [
        'amount' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrderModel::class, 'order_id');
    }
}
