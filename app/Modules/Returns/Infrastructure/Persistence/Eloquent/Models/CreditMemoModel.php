<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class CreditMemoModel extends BaseModel
{
    protected $table = 'credit_memos';

    protected $fillable = [
        'tenant_id', 'reference_number', 'stock_return_id', 'party_id', 'party_type',
        'status', 'amount', 'currency', 'issue_date', 'applied_date', 'voided_date',
        'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'      => 'integer',
        'stock_return_id'=> 'integer',
        'party_id'       => 'integer',
        'amount'         => 'float',
        'issue_date'     => 'datetime',
        'applied_date'   => 'datetime',
        'voided_date'    => 'datetime',
        'metadata'       => 'array',
    ];
}
