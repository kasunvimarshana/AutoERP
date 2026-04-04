<?php
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class RefundModel extends BaseModel
{
    protected $table = 'refunds';

    protected $fillable = [
        'tenant_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'reason',
        'processed_by',
        'processed_at',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount'       => 'float',
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(PaymentModel::class, 'payment_id');
    }
}
