<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Notification\Enums\NotificationType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Notification Channel Model
 *
 * Represents a configured notification channel (email, SMS, etc.)
 */
class NotificationChannel extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'code',
        'name',
        'type',
        'driver',
        'configuration',
        'is_active',
        'is_default',
        'priority',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope query to active channels only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }
}
