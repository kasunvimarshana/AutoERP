<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'settings',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Restrict query to active tenants only.
     */
    public function scopeActive(
        \Illuminate\Database\Eloquent\Builder $query
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('is_active', true);
    }

    /**
     * Find a tenant by its domain (exact match or wildcard subdomain).
     */
    public function scopeByDomain(
        \Illuminate\Database\Eloquent\Builder $query,
        string $domain
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('domain', $domain);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Read a typed setting from the JSON column with an optional default.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Persist a single setting key into the JSON column.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings       = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Return a safe representation of the tenant for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
