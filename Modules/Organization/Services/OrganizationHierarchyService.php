<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Entities\Organization;

/**
 * Organization Hierarchy Service
 *
 * Advanced service for managing organization hierarchies with:
 * - Efficient hierarchy traversal using materialized paths
 * - Circular reference prevention
 * - Hierarchy caching for performance
 * - Cross-organization access control
 * - Bulk operations
 */
class OrganizationHierarchyService
{
    /**
     * Get all ancestors of an organization (including self).
     */
    public function getAncestorsIncludingSelf(int $organizationId): Collection
    {
        $cacheKey = "org_ancestors_incl_{$organizationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return new Collection();
            }

            $ancestors = $organization->ancestors();
            $ancestors->push($organization);
            
            return $ancestors->sortBy('level');
        });
    }

    /**
     * Get all ancestors of an organization (excluding self).
     */
    public function getAncestors(int $organizationId): Collection
    {
        $cacheKey = "org_ancestors_{$organizationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            $organization = Organization::find($organizationId);
            
            return $organization ? $organization->ancestors() : new Collection();
        });
    }

    /**
     * Get all descendants of an organization (including self).
     */
    public function getDescendantsIncludingSelf(int $organizationId): Collection
    {
        $cacheKey = "org_descendants_incl_{$organizationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return new Collection();
            }

            $descendants = $organization->descendants();
            $descendants->push($organization);
            
            return $descendants->sortBy('level');
        });
    }

    /**
     * Get all descendants of an organization (excluding self).
     */
    public function getDescendants(int $organizationId): Collection
    {
        $cacheKey = "org_descendants_{$organizationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            $organization = Organization::find($organizationId);
            
            return $organization ? $organization->descendants() : new Collection();
        });
    }

    /**
     * Get immediate children of an organization.
     */
    public function getChildren(int $organizationId): Collection
    {
        return Organization::where('parent_id', $organizationId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organization siblings (same parent).
     */
    public function getSiblings(int $organizationId, bool $includeSelf = false): Collection
    {
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            return new Collection();
        }

        $query = Organization::where('parent_id', $organization->parent_id);
        
        if (!$includeSelf) {
            $query->where('id', '!=', $organizationId);
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Get root organization(s) for a tenant.
     */
    public function getRootOrganizations(int $tenantId): Collection
    {
        return Organization::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the path from root to organization as a collection.
     */
    public function getPathFromRoot(int $organizationId): Collection
    {
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            return new Collection();
        }

        $ancestors = $organization->ancestors();
        $ancestors->push($organization);
        
        return $ancestors->sortBy('level');
    }

    /**
     * Check if user has access to organization based on visibility rules.
     * 
     * Visibility rules:
     * - 'own': Only the user's organization
     * - 'children': User's organization and all descendants
     * - 'tree': Entire tree (user's org, ancestors, and descendants)
     * - 'tenant': All organizations in the tenant
     */
    public function hasAccess(int $userId, int $organizationId, string $visibility = 'own'): bool
    {
        $user = \Modules\IAM\Entities\User::find($userId);
        
        if (!$user || !$user->organization_id) {
            return false;
        }

        $userOrgId = $user->organization_id;

        return match ($visibility) {
            'own' => $userOrgId === $organizationId,
            'children' => $this->isInSubtree($organizationId, $userOrgId),
            'tree' => $this->isInSameTree($organizationId, $userOrgId),
            'tenant' => $this->isInSameTenant($organizationId, $userOrgId),
            default => false,
        };
    }

    /**
     * Check if target organization is in subtree of reference organization.
     */
    public function isInSubtree(int $targetOrgId, int $referenceOrgId): bool
    {
        if ($targetOrgId === $referenceOrgId) {
            return true;
        }

        $descendants = $this->getDescendants($referenceOrgId);
        
        return $descendants->contains('id', $targetOrgId);
    }

    /**
     * Check if organizations are in the same tree (share a root).
     */
    public function isInSameTree(int $org1Id, int $org2Id): bool
    {
        $org1 = Organization::find($org1Id);
        $org2 = Organization::find($org2Id);
        
        if (!$org1 || !$org2) {
            return false;
        }

        return $org1->root()->id === $org2->root()->id;
    }

    /**
     * Check if organizations belong to the same tenant.
     */
    public function isInSameTenant(int $org1Id, int $org2Id): bool
    {
        $org1 = Organization::find($org1Id);
        $org2 = Organization::find($org2Id);
        
        if (!$org1 || !$org2) {
            return false;
        }

        return $org1->tenant_id === $org2->tenant_id;
    }

    /**
     * Get accessible organizations for a user based on visibility.
     */
    public function getAccessibleOrganizations(int $userId, string $visibility = 'own'): Collection
    {
        $user = \Modules\IAM\Entities\User::find($userId);
        
        if (!$user || !$user->organization_id) {
            return new Collection();
        }

        $userOrgId = $user->organization_id;
        $userOrg = Organization::find($userOrgId);

        if (!$userOrg) {
            return new Collection();
        }

        return match ($visibility) {
            'own' => new Collection([$userOrg]),
            'children' => $this->getDescendantsIncludingSelf($userOrgId),
            'tree' => $this->getFullTree($userOrgId),
            'tenant' => Organization::where('tenant_id', $userOrg->tenant_id)->get(),
            default => new Collection(),
        };
    }

    /**
     * Get full tree (ancestors, self, and descendants).
     */
    public function getFullTree(int $organizationId): Collection
    {
        $ancestors = $this->getAncestors($organizationId);
        $descendants = $this->getDescendantsIncludingSelf($organizationId);
        
        return $ancestors->merge($descendants)->unique('id')->sortBy('level');
    }

    /**
     * Move organization to a new parent (re-parent).
     */
    public function moveOrganization(int $organizationId, ?int $newParentId): Organization
    {
        DB::beginTransaction();
        try {
            $organization = Organization::findOrFail($organizationId);

            // Validate not moving to self
            if ($newParentId === $organizationId) {
                throw new \InvalidArgumentException('Organization cannot be its own parent');
            }

            // Validate not creating circular reference
            if ($newParentId && $this->wouldCreateCircularReference($organizationId, $newParentId)) {
                throw new \InvalidArgumentException('Cannot create circular reference in hierarchy');
            }

            // Update parent
            $organization->parent_id = $newParentId;
            $organization->save();

            // Clear cache for affected organizations
            $this->clearHierarchyCache($organizationId);

            DB::commit();
            return $organization->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if moving would create a circular reference.
     */
    private function wouldCreateCircularReference(int $organizationId, int $newParentId): bool
    {
        // If new parent is a descendant of the organization, it would create a circle
        $descendants = $this->getDescendants($organizationId);
        
        return $descendants->contains('id', $newParentId);
    }

    /**
     * Clear hierarchy cache for an organization and its relatives.
     */
    public function clearHierarchyCache(int $organizationId): void
    {
        $cacheKeys = [
            "org_ancestors_{$organizationId}",
            "org_ancestors_incl_{$organizationId}",
            "org_descendants_{$organizationId}",
            "org_descendants_incl_{$organizationId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Also clear cache for ancestors and descendants
        $organization = Organization::find($organizationId);
        if ($organization) {
            foreach ($organization->ancestors() as $ancestor) {
                Cache::forget("org_descendants_{$ancestor->id}");
                Cache::forget("org_descendants_incl_{$ancestor->id}");
            }
            
            foreach ($organization->descendants() as $descendant) {
                Cache::forget("org_ancestors_{$descendant->id}");
                Cache::forget("org_ancestors_incl_{$descendant->id}");
            }
        }
    }

    /**
     * Get organization depth in hierarchy (0 = root).
     */
    public function getDepth(int $organizationId): int
    {
        $organization = Organization::find($organizationId);
        
        return $organization ? $organization->level : 0;
    }

    /**
     * Get organization breadcrumb (path from root).
     */
    public function getBreadcrumb(int $organizationId): array
    {
        $path = $this->getPathFromRoot($organizationId);
        
        return $path->map(function ($org) {
            return [
                'id' => $org->id,
                'name' => $org->getTranslation('name'),
                'code' => $org->code,
                'level' => $org->level,
            ];
        })->toArray();
    }

    /**
     * Bulk move organizations to a new parent.
     */
    public function bulkMoveOrganizations(array $organizationIds, ?int $newParentId): int
    {
        DB::beginTransaction();
        try {
            $moved = 0;
            
            foreach ($organizationIds as $orgId) {
                try {
                    $this->moveOrganization($orgId, $newParentId);
                    $moved++;
                } catch (\Exception $e) {
                    // Log error but continue with others
                    \Log::warning("Failed to move organization {$orgId}: {$e->getMessage()}");
                }
            }

            DB::commit();
            return $moved;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
