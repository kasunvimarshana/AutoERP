<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Database\Factories\BranchFactory;
use Modules\Organization\Enums\BranchStatus;

/**
 * Branch Model
 *
 * Represents a physical branch location of an organization
 * Supports cross-branch operations for vehicle service history
 * Each branch can service customers and vehicles from any branch
 *
 * @property int $id
 * @property int $organization_id Parent organization ID
 * @property string $branch_code Unique branch code
 * @property string $name Branch name
 * @property BranchStatus $status Branch status (active, inactive, maintenance)
 * @property string|null $manager_name Branch manager name
 * @property string|null $email Branch email
 * @property string|null $phone Branch phone
 * @property string|null $address Branch address
 * @property string|null $city City
 * @property string|null $state State/Province
 * @property string|null $postal_code Postal/ZIP code
 * @property string|null $country Country
 * @property float|null $latitude GPS latitude
 * @property float|null $longitude GPS longitude
 * @property array|null $operating_hours Operating hours (JSON)
 * @property array|null $services_offered Services offered (JSON array)
 * @property int|null $capacity_vehicles Maximum vehicle capacity per day
 * @property int|null $bay_count Number of service bays
 * @property array|null $metadata Additional JSON metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Branch extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * The table associated with the model
     */
    protected $table = 'branches';

    /**
     * The attributes that are mass assignable
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'branch_code',
        'name',
        'status',
        'manager_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'operating_hours',
        'services_offered',
        'capacity_vehicles',
        'bay_count',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'organization_id' => 'integer',
        'status' => BranchStatus::class,
        'latitude' => 'float',
        'longitude' => 'float',
        'operating_hours' => 'array',
        'services_offered' => 'array',
        'capacity_vehicles' => 'integer',
        'bay_count' => 'integer',
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
    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    /**
     * Get the organization that owns this branch
     *
     * @return BelongsTo<Organization, Branch>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Scope query to active branches only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', BranchStatus::ACTIVE->value);
    }

    /**
     * Scope query to branches by organization
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope query to branches by city
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Check if branch is active
     */
    public function isActive(): bool
    {
        return $this->status === BranchStatus::ACTIVE;
    }

    /**
     * Check if branch is under maintenance
     */
    public function isUnderMaintenance(): bool
    {
        return $this->status === BranchStatus::MAINTENANCE;
    }

    /**
     * Check if branch has GPS coordinates
     */
    public function hasGPSCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    /**
     * Get full address as string
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if branch is at full capacity
     */
    public function isAtCapacity(int $currentVehicles): bool
    {
        if (is_null($this->capacity_vehicles)) {
            return false;
        }

        return $currentVehicles >= $this->capacity_vehicles;
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacity(int $currentVehicles): int
    {
        if (is_null($this->capacity_vehicles)) {
            return PHP_INT_MAX;
        }

        return max(0, $this->capacity_vehicles - $currentVehicles);
    }
}
