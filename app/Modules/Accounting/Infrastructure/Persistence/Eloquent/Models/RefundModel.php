<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class RefundModel extends BaseModel
{
    protected $table = 'refunds';

    protected $fillable = [
        'tenant_id', 'original_payment_id',
        'amount', 'currency', 'status', 'reason',
        'reference', 'refund_date', 'journal_entry_id',
    ];

    protected $casts = [
        'id'                  => 'int',
        'tenant_id'           => 'int',
        'original_payment_id' => 'int',
        'amount'              => 'float',
        'journal_entry_id'    => 'int',
        'refund_date'         => 'date',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];
}
