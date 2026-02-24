<?php

namespace Modules\Purchase\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionLineModel extends Model
{
    use HasUuids;

    protected $table = 'purchase_requisition_lines';

    protected $fillable = [
        'id', 'requisition_id', 'product_id', 'qty', 'unit_price',
        'line_total', 'uom', 'required_by_date', 'justification', 'sort_order',
    ];

    protected $casts = [
        'required_by_date' => 'date',
    ];

    public function requisition()
    {
        return $this->belongsTo(PurchaseRequisitionModel::class, 'requisition_id');
    }
}
