<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TransactionModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'transactions';

    protected $fillable = [
        'tenant_id',
        'type',
        'reference_type',
        'reference_id',
        'status',
        'description',
        'transaction_date',
        'total_amount',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount'     => 'decimal:2',
    ];
}
