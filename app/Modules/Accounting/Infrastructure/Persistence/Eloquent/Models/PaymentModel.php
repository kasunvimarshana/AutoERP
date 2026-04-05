<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PaymentModel extends BaseModel
{
    protected $table = 'payments';

    protected $fillable = [
        'tenant_id', 'payable_type', 'payable_id',
        'amount', 'currency', 'payment_method', 'status', 'direction',
        'reference', 'notes', 'payment_date', 'journal_entry_id',
    ];

    protected $casts = [
        'id'               => 'int',
        'tenant_id'        => 'int',
        'payable_id'       => 'int',
        'amount'           => 'float',
        'journal_entry_id' => 'int',
        'payment_date'     => 'date',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];
}
