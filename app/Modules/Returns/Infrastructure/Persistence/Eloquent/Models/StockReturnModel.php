<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class StockReturnModel extends BaseModel
{
    use HasAudit;

    protected $table = 'stock_returns';

    protected $fillable = [
        'tenant_id', 'reference_number', 'return_type', 'status', 'party_id', 'party_type',
        'original_reference_id', 'original_reference_type', 'return_reason', 'total_amount',
        'currency', 'restock', 'restock_location_id', 'restocking_fee', 'credit_memo_issued',
        'credit_memo_reference', 'approved_by', 'approved_at', 'processed_by', 'processed_at',
        'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'            => 'integer',
        'party_id'             => 'integer',
        'original_reference_id'=> 'integer',
        'total_amount'         => 'float',
        'restock'              => 'boolean',
        'restock_location_id'  => 'integer',
        'restocking_fee'       => 'float',
        'credit_memo_issued'   => 'boolean',
        'approved_by'          => 'integer',
        'approved_at'          => 'datetime',
        'processed_by'         => 'integer',
        'processed_at'         => 'datetime',
        'metadata'             => 'array',
    ];
}
