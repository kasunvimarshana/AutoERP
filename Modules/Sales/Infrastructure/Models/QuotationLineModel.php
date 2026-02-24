<?php
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QuotationLineModel extends Model
{
    use HasUuids;
    protected $table = 'sales_quotation_lines';
    protected $fillable = [
        'id', 'quotation_id', 'product_id', 'description', 'qty',
        'unit_price', 'discount_pct', 'discount_amount', 'tax_rate',
        'tax_amount', 'line_total', 'uom', 'sort_order',
    ];
    public $timestamps = false;
}
