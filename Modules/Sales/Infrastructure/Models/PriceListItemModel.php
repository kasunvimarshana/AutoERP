<?php
namespace Modules\Sales\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PriceListItemModel extends Model
{
    use HasUuids, HasTenantScope;

    protected $table = 'sales_price_list_items';

    protected $fillable = [
        'id', 'tenant_id', 'price_list_id', 'product_id',
        'variant_id', 'strategy', 'amount', 'min_qty', 'uom',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceListModel::class, 'price_list_id');
    }
}
