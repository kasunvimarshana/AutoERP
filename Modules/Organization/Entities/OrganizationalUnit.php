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
use Modules\Organization\Database\Factories\OrganizationalUnitFactory;
use Modules\Organization\Traits\Hierarchical;

/**
 * OrganizationalUnit Entity
 *
 * Represents a department, division, team, or other organizational unit.
 * Links organizations and locations with hierarchical structure.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $organization_id
 * @property int|null $location_id
 * @property int|null $parent_unit_id
 * @property string $code
 * @property array $name
 * @property array|null $description
 * @property string $unit_type
 * @property string $status
 * @property int|null $manager_id
 * @property string|null $email
 * @property string|null $phone
 * @property array|null $settings
 * @property array|null $metadata
 * @property int $level
 * @property string|null $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class OrganizationalUnit extends Model
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
        'location_id',
        'parent_unit_id',
        'code',
        'name',
        'description',
        'unit_type',
        'status',
        'manager_id',
        'email',
        'phone',
        'settings',
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
        'settings' => 'array',
        'metadata' => 'array',
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
     * Get the organization this unit belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the location this unit is assigned to.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the parent organizational unit.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'parent_unit_id');
    }

    /**
     * Get the child organizational units.
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'parent_unit_id');
    }

    /**
     * Get the manager of this unit.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    /**
     * Check if unit is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
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
     * Get the full hierarchical name.
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
        return 'parent_unit_id';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrganizationalUnitFactory
    {
        return OrganizationalUnitFactory::new();
    }
}
