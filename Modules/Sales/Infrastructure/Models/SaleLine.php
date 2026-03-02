<?php
declare(strict_types=1);
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Model;
class SaleLine extends Model {
    protected $table = 'sale_lines';
    protected $fillable = [
        'tenant_id','sale_id','product_id','variant_id',
        'quantity','unit_price','discount_percent','tax_percent','line_total','notes',
    ];
    protected $casts = [
        'quantity'        => 'decimal:4',
        'unit_price'      => 'decimal:4',
        'discount_percent'=> 'decimal:4',
        'tax_percent'     => 'decimal:4',
        'line_total'      => 'decimal:4',
    ];
    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
