<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class StockMovementModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'inventory_stock_movements';
    public $timestamps = false;
    protected $fillable = [
        'id', 'tenant_id', 'type', 'product_id', 'variant_id',
        'from_location_id', 'to_location_id', 'qty', 'unit_cost',
        'reference_type', 'reference_id', 'lot_number', 'serial_number',
        'notes', 'posted_by', 'posted_at', 'created_by',
    ];
    protected $casts = ['posted_at' => 'datetime', 'qty' => 'string', 'unit_cost' => 'string'];
}
