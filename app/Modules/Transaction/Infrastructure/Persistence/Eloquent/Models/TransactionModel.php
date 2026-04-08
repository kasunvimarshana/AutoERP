<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TransactionModel extends BaseModel
{
    protected $table = 'transactions';

    protected $fillable = [
        'tenant_id',
        'reference_number',
        'transaction_type',
        'status',
        'amount',
        'currency_code',
        'exchange_rate',
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'posted_at',
        'voided_at',
        'void_reason',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'    => 'integer',
        'amount'       => 'float',
        'exchange_rate' => 'float',
        'reference_id' => 'integer',
        'posted_at'    => 'datetime',
        'voided_at'    => 'datetime',
        'metadata'     => 'array',
    ];
}
