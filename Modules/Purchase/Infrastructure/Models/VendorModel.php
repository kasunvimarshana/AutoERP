<?php
namespace Modules\Purchase\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class VendorModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'purchase_vendors';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'email', 'phone', 'tax_id',
        'currency', 'payment_terms', 'status', 'rating',
        'bank_details', 'address', 'created_by', 'updated_by',
    ];
    protected $casts = ['bank_details' => 'array', 'address' => 'array', 'rating' => 'float'];
}
