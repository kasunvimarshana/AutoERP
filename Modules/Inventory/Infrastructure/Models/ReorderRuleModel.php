<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class ReorderRuleModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'inventory_reorder_rules';
    protected $fillable = [
        'id', 'tenant_id', 'product_id', 'location_id',
        'reorder_point', 'min_qty', 'max_qty', 'lead_time_days', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean', 'lead_time_days' => 'integer'];
}
