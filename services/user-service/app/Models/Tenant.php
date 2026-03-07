<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'plan',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config'    => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // -------------------------------------------------------------------------
    // Config helpers
    // -------------------------------------------------------------------------

    /**
     * Read a nested config value using dot-notation, e.g. 'mail.host'.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Write a nested config value using dot-notation (mutates the array, persists on save).
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDomain($query, string $domain): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('domain', $domain);
    }

    // -------------------------------------------------------------------------
    // Feature/plan helpers
    // -------------------------------------------------------------------------

    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function isPaidPlan(): bool
    {
        return ! in_array($this->plan, ['free'], true);
    }
}
