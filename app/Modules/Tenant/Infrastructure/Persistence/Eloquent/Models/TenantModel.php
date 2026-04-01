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

    protected $fillable = [
        'name',
        'domain',
        'logo_path',
        'database_config',
        'mail_config',
        'cache_config',
        'queue_config',
        'feature_flags',
        'api_keys',
        'active',
    ];

    protected $casts = [
        'database_config' => 'array',
        'mail_config' => 'array',
        'cache_config' => 'array',
        'queue_config' => 'array',
        'feature_flags' => 'array',
        'api_keys' => 'array',
        'active' => 'boolean',
    ];

    public function attachments()
    {
        return $this->hasMany(TenantAttachmentModel::class, 'tenant_id');
    }
}
