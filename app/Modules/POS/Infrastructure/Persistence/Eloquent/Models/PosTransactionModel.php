<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PosTransactionModel extends BaseModel
{
    protected $table = 'pos_transactions';
    protected $fillable = [
        'tenant_id', 'session_id', 'customer_id', 'type', 'status', 'currency',
        'subtotal', 'tax_total', 'discount_total', 'total',
        'payment_method', 'amount_tendered', 'change_given', 'reference', 'notes',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'session_id'      => 'int',
        'customer_id'     => 'int',
        'subtotal'        => 'float',
        'tax_total'       => 'float',
        'discount_total'  => 'float',
        'total'           => 'float',
        'amount_tendered' => 'float',
        'change_given'    => 'float',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
