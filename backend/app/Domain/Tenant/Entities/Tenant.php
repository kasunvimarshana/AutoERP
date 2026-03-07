<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Domain entity for a Tenant.
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'plan',
        'status',
        'config',
        'max_users',
        'timezone',
        'locale',
        'currency',
        'logo_url',
        'metadata',
    ];

    protected $casts = [
        'config'   => 'array',
        'metadata' => 'array',
        'max_users' => 'integer',
    ];

    protected $hidden = ['config'];

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(\App\Models\Webhook::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Retrieve a tenant config value with a default fallback. */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }
}
