<?php
declare(strict_types=1);
namespace Modules\Procurement\Infrastructure\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderLine extends Model {
    protected $table = 'purchase_order_lines';
    protected $fillable = [
        'tenant_id','purchase_order_id','product_id','variant_id',
        'quantity','unit_cost','tax_percent','line_total','received_quantity','notes',
    ];
    protected $casts = [
        'quantity'          => 'decimal:4',
        'unit_cost'         => 'decimal:4',
        'tax_percent'       => 'decimal:4',
        'line_total'        => 'decimal:4',
        'received_quantity' => 'decimal:4',
    ];
    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
