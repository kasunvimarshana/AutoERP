<?php
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class QuotationModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'sales_quotations';
    protected $fillable = [
        'id', 'tenant_id', 'number', 'customer_id', 'status',
        'subtotal', 'tax_total', 'total', 'currency',
        'notes', 'expires_at', 'created_by', 'updated_by',
    ];
    protected $casts = ['expires_at' => 'date'];
    public function lines()
    {
        return $this->hasMany(QuotationLineModel::class, 'quotation_id');
    }
}
