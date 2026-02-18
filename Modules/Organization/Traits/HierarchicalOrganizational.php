<?php

declare(strict_types=1);

namespace Modules\Organization\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Entities\Location;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Services\OrganizationHierarchyService;

/**
 * Hierarchical Organizational Trait
 *
 * Enhanced organizational trait with hierarchical organization support.
 * Provides methods to query entities across organization hierarchies.
 * 
 * Use this trait on entities that need hierarchical organization filtering,
 * such as when a user needs to see data from their organization and all child organizations.
 */
trait HierarchicalOrganizational
{
    /**
     * Boot the hierarchical organizational trait for a model.
     */
    public static function bootHierarchicalOrganizational(): void
    {
        static::creating(function ($model) {
            // Auto-assign organization_id if not set
            if (!$model->organization_id && session()->has('organization_id')) {
                $model->organization_id = session('organization_id');
            }

            // Auto-assign location_id if not set
            if (!$model->location_id && session()->has('location_id')) {
                $model->location_id = session('location_id');
            }
        });
    }

    /**
     * Get the organization this entity belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the location this entity belongs to.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope query to a specific organization.
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where($this->getTable() . '.organization_id', $organizationId);
    }

    /**
     * Scope query to a specific location.
     */
    public function scopeForLocation(Builder $query, int $locationId): Builder
    {
        return $query->where($this->getTable() . '.location_id', $locationId);
    }

    /**
     * Scope query to multiple organizations.
     */
    public function scopeForOrganizations(Builder $query, array $organizationIds): Builder
    {
        return $query->whereIn($this->getTable() . '.organization_id', $organizationIds);
    }

    /**
     * Scope query to multiple locations.
     */
    public function scopeForLocations(Builder $query, array $locationIds): Builder
    {
        return $query->whereIn($this->getTable() . '.location_id', $locationIds);
    }

    /**
     * Scope query to organization and all its descendants.
     * 
     * @param Builder $query
     * @param int $organizationId The root organization ID
     * @return Builder
     */
    public function scopeForOrganizationTree(Builder $query, int $organizationId): Builder
    {
        $hierarchyService = app(OrganizationHierarchyService::class);
        $organizations = $hierarchyService->getDescendantsIncludingSelf($organizationId);
        $organizationIds = $organizations->pluck('id')->toArray();

        return $query->whereIn($this->getTable() . '.organization_id', $organizationIds);
    }

    /**
     * Scope query to organization and all its children (not deeper descendants).
     * 
     * @param Builder $query
     * @param int $organizationId The parent organization ID
     * @return Builder
     */
    public function scopeForOrganizationAndChildren(Builder $query, int $organizationId): Builder
    {
        $hierarchyService = app(OrganizationHierarchyService::class);
        $children = $hierarchyService->getChildren($organizationId);
        $organizationIds = $children->pluck('id')->push($organizationId)->toArray();

        return $query->whereIn($this->getTable() . '.organization_id', $organizationIds);
    }

    /**
     * Scope query to organization's ancestors (parent, grandparent, etc).
     * 
     * @param Builder $query
     * @param int $organizationId The child organization ID
     * @param bool $includeSelf Whether to include the organization itself
     * @return Builder
     */
    public function scopeForOrganizationAncestors(
        Builder $query, 
        int $organizationId, 
        bool $includeSelf = false
    ): Builder {
        $hierarchyService = app(OrganizationHierarchyService::class);
        
        if ($includeSelf) {
            $organizations = $hierarchyService->getAncestorsIncludingSelf($organizationId);
        } else {
            $organizations = $hierarchyService->getAncestors($organizationId);
        }
        
        $organizationIds = $organizations->pluck('id')->toArray();

        return $query->whereIn($this->getTable() . '.organization_id', $organizationIds);
    }

    /**
     * Scope query to current user's accessible organizations.
     * 
     * This method uses the current user's organization visibility settings
     * to determine which organizations they can see data from.
     * 
     * @param Builder $query
     * @param string $visibility Visibility level: 'own', 'children', 'tree', 'tenant'
     * @return Builder
     */
    public function scopeForCurrentUserOrganizations(
        Builder $query, 
        string $visibility = 'own'
    ): Builder {
        $user = auth()->user();
        
        if (!$user || !$user->organization_id) {
            // No user or organization, return empty result
            return $query->whereRaw('1 = 0');
        }

        $hierarchyService = app(OrganizationHierarchyService::class);
        $organizations = $hierarchyService->getAccessibleOrganizations($user->id, $visibility);
        $organizationIds = $organizations->pluck('id')->toArray();

        if (empty($organizationIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($this->getTable() . '.organization_id', $organizationIds);
    }

    /**
     * Scope query to organizations at a specific level in the hierarchy.
     * 
     * @param Builder $query
     * @param int $level The hierarchy level (0 = root, 1 = first child, etc)
     * @return Builder
     */
    public function scopeForOrganizationLevel(Builder $query, int $level): Builder
    {
        return $query->whereHas('organization', function ($q) use ($level) {
            $q->where('level', $level);
        });
    }

    /**
     * Scope query to root organizations only.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeForRootOrganizations(Builder $query): Builder
    {
        return $query->whereHas('organization', function ($q) {
            $q->whereNull('parent_id');
        });
    }

    /**
     * Scope query to leaf organizations (organizations with no children).
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeForLeafOrganizations(Builder $query): Builder
    {
        return $query->whereHas('organization', function ($q) {
            $q->whereDoesntHave('children');
        });
    }

    /**
     * Check if entity belongs to a specific organization or its descendants.
     */
    public function belongsToOrganizationTree(int $organizationId): bool
    {
        if (!$this->organization_id) {
            return false;
        }

        if ($this->organization_id === $organizationId) {
            return true;
        }

        $hierarchyService = app(OrganizationHierarchyService::class);
        
        return $hierarchyService->isInSubtree($this->organization_id, $organizationId);
    }

    /**
     * Check if entity belongs to same organization tree as another organization.
     */
    public function belongsToSameTree(int $organizationId): bool
    {
        if (!$this->organization_id) {
            return false;
        }

        $hierarchyService = app(OrganizationHierarchyService::class);
        
        return $hierarchyService->isInSameTree($this->organization_id, $organizationId);
    }

    /**
     * Get all sibling entities (entities in sibling organizations).
     */
    public function scopeSiblingOrganizations(Builder $query): Builder
    {
        if (!$this->organization_id) {
            return $query->whereRaw('1 = 0');
        }

        $hierarchyService = app(OrganizationHierarchyService::class);
        $siblings = $hierarchyService->getSiblings($this->organization_id, false);
        $siblingIds = $siblings->pluck('id')->toArray();

        return $query->whereIn($this->getTable() . '.organization_id', $siblingIds);
    }
}
