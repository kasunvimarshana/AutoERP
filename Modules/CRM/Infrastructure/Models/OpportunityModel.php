<?php
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class OpportunityModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'crm_opportunities';
    protected $fillable = [
        'id', 'tenant_id', 'title', 'lead_id', 'contact_id', 'account_id',
        'stage', 'expected_revenue', 'probability', 'assigned_to',
        'expected_close_date', 'won_at', 'lost_at', 'lost_reason',
        'currency', 'description', 'created_by', 'updated_by',
    ];
    protected $casts = [
        'probability' => 'float',
        'expected_close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
    ];
}
