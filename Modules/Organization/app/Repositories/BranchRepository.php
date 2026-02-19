<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\Enums\BranchStatus;
use Modules\Organization\Models\Branch;

/**
 * Branch Repository
 *
 * Handles data access for Branch model
 * Extends BaseRepository for common CRUD operations
 */
class BranchRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new Branch;
    }

    /**
     * Check if branch code exists
     */
    public function branchCodeExists(string $branchCode, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('branch_code', $branchCode);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get branches by organization
     */
    public function getByOrganization(int $organizationId): Collection
    {
        return $this->model->newQuery()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active branches
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', BranchStatus::ACTIVE->value)
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active branches by organization
     */
    public function getActiveByOrganization(int $organizationId): Collection
    {
        return $this->model->newQuery()
            ->where('organization_id', $organizationId)
            ->where('status', BranchStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branches by status
     */
    public function getByStatus(BranchStatus $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status->value)
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branches by city
     */
    public function getByCity(string $city): Collection
    {
        return $this->model->newQuery()
            ->where('city', $city)
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search branches by name or branch code
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('branch_code', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branches within radius (in kilometers)
     *
     * @param  float  $radius  Radius in kilometers
     */
    public function getNearby(float $latitude, float $longitude, float $radius = 10): Collection
    {
        // Using Haversine formula for distance calculation
        return $this->model->newQuery()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('
                *,
                (
                    6371 * acos(
                        cos(radians(?)) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(latitude))
                    )
                ) AS distance
            ', [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->with('organization')
            ->get();
    }

    /**
     * Get branches with available capacity
     */
    public function getWithAvailableCapacity(): Collection
    {
        return $this->model->newQuery()
            ->where('status', BranchStatus::ACTIVE->value)
            ->whereNotNull('capacity_vehicles')
            ->where('capacity_vehicles', '>', 0)
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branches by country
     */
    public function getByCountry(string $countryCode): Collection
    {
        return $this->model->newQuery()
            ->where('country', $countryCode)
            ->with('organization')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branch by code
     */
    public function findByCode(string $branchCode): ?Model
    {
        return $this->model->newQuery()
            ->where('branch_code', $branchCode)
            ->with('organization')
            ->first();
    }
}
