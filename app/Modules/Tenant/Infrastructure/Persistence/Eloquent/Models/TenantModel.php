<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class TenantModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'tenants';

    /**
     * Attributes that should be hidden from JSON responses.
     */
    protected $hidden = [
        'api_keys',
        'database_config',
    ];

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo_path',
        'database_config',
        'mail_config',
        'cache_config',
        'queue_config',
        'feature_flags',
        'api_keys',
        'settings',
        'plan',
        'tenant_plan_id',
        'status',
        'active',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'database_config' => 'array',
        'mail_config' => 'array',
        'cache_config' => 'array',
        'queue_config' => 'array',
        'feature_flags' => 'array',
        'api_keys' => 'array',
        'settings' => 'array',
        'active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function attachments()
    {
        return $this->hasMany(TenantAttachmentModel::class, 'tenant_id');
    }
}
