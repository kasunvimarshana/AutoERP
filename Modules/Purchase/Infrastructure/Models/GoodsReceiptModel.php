<?php
namespace Modules\Purchase\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class GoodsReceiptModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'purchase_goods_receipts';
    protected $fillable = [
        'id', 'tenant_id', 'purchase_order_id', 'reference',
        'notes', 'received_at', 'received_by', 'created_by', 'updated_by',
    ];
    protected $casts = ['received_at' => 'datetime'];
    public function lines()
    {
        return $this->hasMany(GoodsReceiptLineModel::class, 'goods_receipt_id');
    }
}
