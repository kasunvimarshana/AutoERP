<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'id',
        'name',
        'subdomain',
        'plan',
        'status',
        'settings',
        'features',
        'config',
        'metadata',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings'             => 'array',
        'features'             => 'array',
        'config'               => 'array',
        'metadata'             => 'array',
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Users belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Organizations belonging to this tenant.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'tenant_id');
    }

    /**
     * Audit logs for this tenant.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'tenant_id');
    }

    /**
     * Scope to active tenants.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Get a specific feature flag.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return (bool) ($features[$feature] ?? config("tenant.default_features.{$feature}", false));
    }

    /**
     * Get the database connection name for this tenant.
     */
    public function getConnectionName(): string
    {
        return 'tenant_' . $this->id;
    }

    /**
     * Get the database schema name for this tenant.
     */
    public function getSchemaName(): string
    {
        return config('tenant.db_prefix', 'tenant_') . $this->subdomain;
    }

    /**
     * Get setting value with dot notation support.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return data_get($settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Check if tenant plan allows a specific limit.
     */
    public function withinPlanLimit(string $resource, int $current): bool
    {
        $limit = config("tenant.plans.{$this->plan}.{$resource}", 0);
        return $limit === -1 || $current < $limit;
    }
}
