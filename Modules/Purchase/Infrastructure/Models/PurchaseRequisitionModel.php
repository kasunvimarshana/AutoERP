<?php

namespace Modules\Purchase\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PurchaseRequisitionModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;

    protected $table = 'purchase_requisitions';

    protected $fillable = [
        'id', 'tenant_id', 'number', 'requested_by', 'status',
        'total_amount', 'department', 'required_by', 'notes',
        'approved_by', 'approved_at', 'rejected_by', 'rejection_reason', 'rejected_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'required_by' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PurchaseRequisitionLineModel::class, 'requisition_id');
    }
}
