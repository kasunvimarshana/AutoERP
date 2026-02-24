<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class LocationModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'inventory_locations';
    protected $fillable = [
        'id', 'tenant_id', 'warehouse_id', 'name', 'code',
        'type', 'parent_id', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];
    public function parent()
    {
        return $this->belongsTo(LocationModel::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(LocationModel::class, 'parent_id');
    }
}
