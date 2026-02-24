<?php
namespace Modules\Purchase\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class PurchaseOrderModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'purchase_orders';
    protected $fillable = [
        'id', 'tenant_id', 'number', 'vendor_id', 'status',
        'subtotal', 'tax_total', 'total', 'currency',
        'delivery_date', 'approved_at', 'approved_by', 'notes',
        'created_by', 'updated_by',
    ];
    protected $casts = [
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
    ];
    public function lines()
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }
}
