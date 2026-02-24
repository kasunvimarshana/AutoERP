<?php
namespace Modules\Sales\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PriceListModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'sales_price_lists';

    protected $fillable = [
        'id', 'tenant_id', 'name', 'currency_code',
        'is_active', 'valid_from', 'valid_to', 'customer_group',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PriceListItemModel::class, 'price_list_id');
    }
}
