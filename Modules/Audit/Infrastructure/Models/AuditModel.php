<?php
namespace Modules\Audit\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class AuditModel extends Model
{
    use HasUuids, HasTenantScope;
    public $timestamps = true;
    const UPDATED_AT = null;
    protected $table = 'audit_logs';
    protected $fillable = [
        'id', 'tenant_id', 'user_id', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at',
    ];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    public static function boot(): void
    {
        parent::boot();
    }
}
