<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class WarehouseModel extends Model
{
    use HasUuids, HasTenantScope, HasAuditLog;
    protected $table = 'inventory_warehouses';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'code', 'address',
        'responsible_user_id', 'is_active', 'created_by', 'updated_by',
    ];
    protected $casts = ['address' => 'array', 'is_active' => 'boolean'];
    public function locations()
    {
        return $this->hasMany(LocationModel::class, 'warehouse_id');
    }
}
