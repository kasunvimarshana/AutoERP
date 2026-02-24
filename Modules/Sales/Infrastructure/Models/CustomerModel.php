<?php
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class CustomerModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'sales_customers';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'type', 'email', 'phone',
        'credit_limit', 'status', 'price_list_id', 'payment_terms',
        'currency', 'tax_id', 'billing_address', 'created_by', 'updated_by',
    ];
    protected $casts = ['billing_address' => 'array'];
}
