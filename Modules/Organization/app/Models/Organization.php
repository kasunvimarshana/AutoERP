<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Enums\OrganizationStatus;
use Modules\Organization\Enums\OrganizationType;

/**
 * Organization Model
 *
 * Represents a vehicle service center organization/company
 * Can have multiple branches operating under it
 * Supports multi-tenancy with automatic tenant scoping
 *
 * @property int $id
 * @property string $organization_number Unique organization identifier
 * @property string $name Organization name
 * @property string|null $legal_name Legal/registered business name
 * @property OrganizationType $type Organization type (single, multi_branch, franchise)
 * @property OrganizationStatus $status Organization status (active, inactive, suspended)
 * @property string|null $tax_id Tax identification number
 * @property string|null $registration_number Business registration number
 * @property string|null $email Organization email
 * @property string|null $phone Organization phone
 * @property string|null $website Organization website
 * @property string|null $address Organization address
 * @property string|null $city City
 * @property string|null $state State/Province
 * @property string|null $postal_code Postal/ZIP code
 * @property string|null $country Country
 * @property array|null $metadata Additional JSON metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Organization extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * The table associated with the model
     */
    protected $table = 'organizations';

    /**
     * The attributes that are mass assignable
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_number',
        'name',
        'legal_name',
        'type',
        'status',
        'tax_id',
        'registration_number',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => OrganizationType::class,
        'status' => OrganizationStatus::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    /**
     * Get all branches belonging to this organization
     *
     * @return HasMany<Branch>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'organization_id');
    }

    /**
     * Get active branches only
     *
     * @return HasMany<Branch>
     */
    public function activeBranches(): HasMany
    {
        return $this->branches()->where('status', 'active');
    }

    /**
     * Scope query to active organizations only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', OrganizationStatus::ACTIVE->value);
    }

    /**
     * Scope query to organizations by type
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, OrganizationType|string $type)
    {
        $typeValue = $type instanceof OrganizationType ? $type->value : $type;

        return $query->where('type', $typeValue);
    }

    /**
     * Check if organization is active
     */
    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::ACTIVE;
    }

    /**
     * Check if organization has multiple branches
     */
    public function hasMultipleBranches(): bool
    {
        return $this->type === OrganizationType::MULTI_BRANCH || $this->type === OrganizationType::FRANCHISE;
    }

    /**
     * Get the total number of branches
     */
    public function getBranchCount(): int
    {
        return $this->branches()->count();
    }

    /**
     * Get the total number of active branches
     */
    public function getActiveBranchCount(): int
    {
        return $this->activeBranches()->count();
    }
}
