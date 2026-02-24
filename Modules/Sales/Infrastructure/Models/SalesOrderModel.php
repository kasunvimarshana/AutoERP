<?php
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class SalesOrderModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'sales_orders';
    protected $fillable = [
        'id', 'tenant_id', 'number', 'customer_id', 'quotation_id',
        'status', 'subtotal', 'tax_total', 'total', 'currency',
        'promised_delivery_date', 'confirmed_at', 'shipped_at', 'invoiced_at',
        'cancellation_reason', 'notes', 'created_by', 'updated_by',
    ];
    protected $casts = [
        'promised_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'invoiced_at' => 'datetime',
    ];
    public function lines()
    {
        return $this->hasMany(SalesOrderLineModel::class, 'sales_order_id');
    }
}
