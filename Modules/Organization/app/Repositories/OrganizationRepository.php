<?php

declare(strict_types=1);

namespace Modules\Organization\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\Enums\OrganizationStatus;
use Modules\Organization\Enums\OrganizationType;
use Modules\Organization\Models\Organization;

/**
 * Organization Repository
 *
 * Handles data access for Organization model
 * Extends BaseRepository for common CRUD operations
 */
class OrganizationRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new Organization;
    }

    /**
     * Check if organization number exists
     */
    public function organizationNumberExists(string $organizationNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('organization_number', $organizationNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active organizations
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', OrganizationStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organizations by type
     */
    public function getByType(OrganizationType $type): Collection
    {
        return $this->model->newQuery()
            ->where('type', $type->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organizations by status
     */
    public function getByStatus(OrganizationStatus $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search organizations by name or organization number
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('organization_number', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organizations with branch count
     */
    public function getWithBranchCount(): Collection
    {
        return $this->model->newQuery()
            ->withCount('branches')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get multi-branch organizations
     */
    public function getMultiBranch(): Collection
    {
        return $this->model->newQuery()
            ->whereIn('type', [
                OrganizationType::MULTI_BRANCH->value,
                OrganizationType::FRANCHISE->value,
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organizations by country
     */
    public function getByCountry(string $countryCode): Collection
    {
        return $this->model->newQuery()
            ->where('country', $countryCode)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organizations by city
     */
    public function getByCity(string $city): Collection
    {
        return $this->model->newQuery()
            ->where('city', $city)
            ->orderBy('name')
            ->get();
    }
}
