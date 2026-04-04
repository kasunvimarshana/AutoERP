<?php
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PaymentModel extends BaseModel
{
    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'reference_number',
        'status',
        'method',
        'amount',
        'currency',
        'payable_type',
        'payable_id',
        'paid_by',
        'paid_at',
        'notes',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount'     => 'float',
        'paid_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
