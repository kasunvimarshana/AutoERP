<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string|null $logo_path
 * @property array|null $database_config
 * @property array|null $mail_config
 * @property array|null $cache_config
 * @property array|null $queue_config
 * @property array|null $feature_flags
 * @property array|null $api_keys
 * @property array|null $settings
 * @property string $plan
 * @property int|null $tenant_plan_id
 * @property string $status
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $subscription_ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    public function attachments(): HasMany
    {
        return $this->hasMany(TenantAttachmentModel::class, 'tenant_id');
    }

    public function tenantPlan(): BelongsTo
    {
        return $this->belongsTo(TenantPlanModel::class, 'tenant_plan_id');
    }

    public function settingsItems(): HasMany
    {
        return $this->hasMany(TenantSettingModel::class, 'tenant_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'tenant_id');
    }
}
