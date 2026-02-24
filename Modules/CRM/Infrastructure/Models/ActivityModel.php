<?php
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class ActivityModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'crm_activities';
    protected $fillable = [
        'id', 'tenant_id', 'type', 'subject', 'description', 'status',
        'assigned_to', 'related_type', 'related_id', 'due_at', 'completed_at',
        'outcome', 'created_by', 'updated_by',
    ];
    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime'];
}
