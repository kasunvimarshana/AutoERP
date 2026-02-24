<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class ProductCategoryModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'inventory_product_categories';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'parent_id', 'path', 'description',
    ];
    public function parent()
    {
        return $this->belongsTo(ProductCategoryModel::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(ProductCategoryModel::class, 'parent_id');
    }
}
