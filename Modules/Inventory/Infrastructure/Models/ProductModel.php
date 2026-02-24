<?php
namespace Modules\Inventory\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class ProductModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'inventory_products';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'type', 'sku', 'category_id',
        'unit_price', 'cost_price', 'purchase_uom', 'sale_uom', 'inventory_uom',
        'status', 'barcode_ean13', 'track_lots', 'track_serials',
        'description', 'internal_notes', 'reorder_point',
        'created_by', 'updated_by',
    ];
    protected $casts = ['track_lots' => 'boolean', 'track_serials' => 'boolean'];
}
