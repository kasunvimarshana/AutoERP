<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class StockLevelModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'inventory_stock_levels';
    protected $fillable = [
        'id', 'tenant_id', 'product_id', 'variant_id', 'location_id',
        'qty', 'reserved_qty',
    ];
    protected $casts = ['qty' => 'string', 'reserved_qty' => 'string'];
}
