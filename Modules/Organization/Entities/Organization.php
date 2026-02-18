<?php

declare(strict_types=1);

namespace Modules\Organization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Traits\Hierarchical;

/**
 * Organization Entity
 *
 * Represents a hierarchical organization structure (company, subsidiary, branch, etc.)
 * Supports multi-level nesting and tenant isolation.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $parent_id
 * @property string $code
 * @property array $name
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $registration_number
 * @property string $organization_type
 * @property string $status
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $fax
 * @property string|null $website
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array|null $settings
 * @property array|null $features
 * @property array|null $metadata
 * @property int $level
 * @property string|null $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Organization extends Model
{
    use Auditable, HasFactory, Hierarchical, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'legal_name',
        'tax_id',
        'registration_number',
        'organization_type',
        'status',
        'email',
        'phone',
        'fax',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'settings',
        'features',
        'metadata',
        'level',
        'path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['name'];

    /**
     * Get the parent organization.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    /**
     * Get the child organizations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    /**
     * Get the locations belonging to this organization.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the organizational units belonging to this organization.
     */
    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class);
    }

    /**
     * Check if organization is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if organization is headquarters.
     */
    public function isHeadquarters(): bool
    {
        return $this->organization_type === 'headquarters' && $this->parent_id === null;
    }

    /**
     * Get a setting value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     *
     * @param  mixed  $value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Check if a feature is enabled.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get full address as a string.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get the full hierarchical name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name.' / '.$this->getTranslation('name');
        }

        return $this->getTranslation('name');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }
}
