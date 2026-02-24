<?php
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasAuditLog;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class ContactModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope, HasAuditLog;
    protected $table = 'crm_contacts';
    protected $fillable = [
        'id', 'tenant_id', 'first_name', 'last_name', 'email', 'phone',
        'account_id', 'job_title', 'department', 'tags', 'gdpr_consent',
        'created_by', 'updated_by',
    ];
    protected $casts = ['tags' => 'array', 'gdpr_consent' => 'boolean'];
}
