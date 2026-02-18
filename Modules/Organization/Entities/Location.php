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
use Modules\Organization\Database\Factories\LocationFactory;
use Modules\Organization\Traits\Hierarchical;

/**
 * Location Entity
 *
 * Represents a physical or virtual location (office, warehouse, branch, etc.)
 * Belongs to an organization and supports hierarchical structure.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $organization_id
 * @property int|null $parent_location_id
 * @property string $code
 * @property array $name
 * @property array|null $description
 * @property string $location_type
 * @property string $status
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $fax
 * @property string|null $contact_person
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array|null $operating_hours
 * @property string $timezone
 * @property float|null $area_sqm
 * @property int|null $capacity
 * @property array|null $settings
 * @property array|null $features
 * @property array|null $metadata
 * @property int $level
 * @property string|null $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Location extends Model
{
    use Auditable, HasFactory, Hierarchical, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'organization_id',
        'parent_location_id',
        'code',
        'name',
        'description',
        'location_type',
        'status',
        'email',
        'phone',
        'fax',
        'contact_person',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'operating_hours',
        'timezone',
        'area_sqm',
        'capacity',
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
        'description' => 'array',
        'operating_hours' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'area_sqm' => 'decimal:2',
        'capacity' => 'integer',
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
    protected $translatable = ['name', 'description'];

    /**
     * Get the organization this location belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the parent location.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_location_id');
    }

    /**
     * Get the child locations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_location_id');
    }

    /**
     * Get the organizational units at this location.
     */
    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class);
    }

    /**
     * Check if location is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if location is a warehouse.
     */
    public function isWarehouse(): bool
    {
        return in_array($this->location_type, ['warehouse', 'distribution_center']);
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
     * Get the full hierarchical name including organization.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->organization->getTranslation('name');

        if ($this->parent) {
            $name .= ' / '.$this->parent->full_name;
        }

        $name .= ' / '.$this->getTranslation('name');

        return $name;
    }

    /**
     * Override the parent relation name for Hierarchical trait.
     */
    public function getParentKeyName(): string
    {
        return 'parent_location_id';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }
}
