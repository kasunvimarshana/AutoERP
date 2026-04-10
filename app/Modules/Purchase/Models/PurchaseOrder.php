<?php

namespace App\Modules\Purchase\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use UuidTrait;

    protected $fillable = ['po_number', 'supplier_id', 'warehouse_id', 'order_date', 'expected_delivery_date', 'status', 'total_amount', 'created_by'];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
}