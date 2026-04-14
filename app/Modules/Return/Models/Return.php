<?php

namespace App\Modules\Return\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Return extends Model
{
    use UuidTrait;

    protected $fillable = [
        'return_type', 'reference_type', 'reference_id', 'return_number',
        'customer_id', 'supplier_id', 'return_date', 'status', 'credit_memo_id',
        'restocking_fee', 'total_amount', 'notes'
    ];

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function creditMemo()
    {
        return $this->belongsTo(CreditMemo::class);
    }
}