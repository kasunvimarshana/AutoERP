<?php
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class LeadModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'crm_leads';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'company', 'email', 'phone',
        'source', 'status', 'score', 'assigned_to', 'campaign',
        'description', 'converted_at', 'converted_opportunity_id',
        'created_by', 'updated_by',
    ];
    protected $casts = ['score' => 'float', 'converted_at' => 'datetime'];
}
