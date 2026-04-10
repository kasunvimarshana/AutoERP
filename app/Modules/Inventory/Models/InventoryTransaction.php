<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use UuidTrait;

    protected $fillable = [
        'transaction_type', 'reference_type', 'reference_id', 'product_id',
        'warehouse_id', 'location_id', 'batch_id', 'serial_number_id',
        'quantity', 'uom_id', 'unit_cost', 'total_cost', 'valuation_layer_id',
        'created_by'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class);
    }

    public function batch()
    {
        return $this->belongsTo(\App\Modules\Traceability\Models\Batch::class);
    }

    public function serialNumber()
    {
        return $this->belongsTo(\App\Modules\Traceability\Models\SerialNumber::class);
    }

    public function uom()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Uom::class);
    }

    public function valuationLayer()
    {
        return $this->belongsTo(InventoryValuationLayer::class);
    }
}