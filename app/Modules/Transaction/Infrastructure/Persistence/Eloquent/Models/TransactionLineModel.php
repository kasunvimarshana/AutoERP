<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TransactionLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'transaction_lines';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'account_id',
        'product_id',
        'quantity',
        'unit_price',
        'amount',
        'debit',
        'credit',
        'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount'     => 'decimal:4',
        'debit'      => 'decimal:4',
        'credit'     => 'decimal:4',
    ];
}
