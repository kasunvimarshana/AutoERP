<?php

declare(strict_types=1);

namespace Modules\Organization\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Entities\Location;

/**
 * User Organization Trait
 *
 * Provides organization-related methods for User model.
 * Supports hierarchical organization access and context switching.
 */
trait UserOrganization
{
    /**
     * Get the user's primary organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user's primary location.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user's default organization ID.
     */
    public function getDefaultOrganizationId(): ?int
    {
        return $this->organization_id;
    }

    /**
     * Get the user's default location ID.
     */
    public function getDefaultLocationId(): ?int
    {
        return $this->location_id;
    }

    /**
     * Check if user is a super admin (can access all organizations).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasPermission('super_admin') || 
               $this->getSetting('is_super_admin', false);
    }

    /**
     * Check if user is an organization admin.
     */
    public function isOrganizationAdmin(?int $organizationId = null): bool
    {
        $orgId = $organizationId ?? $this->organization_id;
        
        if (!$orgId) {
            return false;
        }

        // Check if user has organization admin permission for this org
        return $this->hasPermission("organization.{$orgId}.admin") ||
               $this->getSetting("organization_admin.{$orgId}", false);
    }

    /**
     * Get a setting value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        if (!isset($this->settings)) {
            return $default;
        }

        $settings = is_array($this->settings) ? $this->settings : json_decode($this->settings, true);
        
        return data_get($settings, $key, $default);
    }

    /**
     * Set a setting value.
     *
     * @param  mixed  $value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = is_array($this->settings) ? $this->settings : json_decode($this->settings ?? '{}', true);
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Switch user's active organization context.
     */
    public function switchOrganization(int $organizationId): bool
    {
        // Validate organization exists and belongs to user's tenant
        $organization = Organization::where('id', $organizationId)
            ->where('tenant_id', $this->tenant_id)
            ->where('status', 'active')
            ->first();

        if (!$organization) {
            return false;
        }

        // Update user's current organization
        $this->update([
            'organization_id' => $organizationId,
            'location_id' => null, // Clear location when switching org
        ]);

        // Store in session
        session(['organization_id' => $organizationId]);
        session()->forget('location_id');

        return true;
    }

    /**
     * Switch user's active location context.
     */
    public function switchLocation(int $locationId): bool
    {
        // Validate location exists and belongs to an accessible organization
        $location = Location::where('id', $locationId)
            ->where('tenant_id', $this->tenant_id)
            ->where('status', 'active')
            ->first();

        if (!$location) {
            return false;
        }

        // Update user's current location and organization
        $this->update([
            'location_id' => $locationId,
            'organization_id' => $location->organization_id,
        ]);

        // Store in session
        session([
            'location_id' => $locationId,
            'organization_id' => $location->organization_id,
        ]);

        return true;
    }

    /**
     * Get organizations the user has access to.
     *
     * @param string $visibility Access level: 'own', 'children', 'tree', 'tenant'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleOrganizations(string $visibility = 'own')
    {
        if ($this->isSuperAdmin()) {
            // Super admins can access all organizations in tenant
            return Organization::where('tenant_id', $this->tenant_id)->get();
        }

        if (!$this->organization_id) {
            return collect();
        }

        $hierarchyService = app(\Modules\Organization\Services\OrganizationHierarchyService::class);
        
        return $hierarchyService->getAccessibleOrganizations($this->id, $visibility);
    }

    /**
     * Check if user can access a specific organization.
     */
    public function canAccessOrganization(int $organizationId, string $visibility = 'own'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->organization_id) {
            return false;
        }

        $hierarchyService = app(\Modules\Organization\Services\OrganizationHierarchyService::class);
        
        return $hierarchyService->hasAccess($this->id, $organizationId, $visibility);
    }

    /**
     * Get user's organization hierarchy breadcrumb.
     */
    public function getOrganizationBreadcrumb(): array
    {
        if (!$this->organization_id) {
            return [];
        }

        $hierarchyService = app(\Modules\Organization\Services\OrganizationHierarchyService::class);
        
        return $hierarchyService->getBreadcrumb($this->organization_id);
    }

    /**
     * Get user's organization visibility setting.
     */
    public function getOrganizationVisibility(): string
    {
        return $this->getSetting('organization_visibility', 'own');
    }

    /**
     * Set user's organization visibility setting.
     */
    public function setOrganizationVisibility(string $visibility): void
    {
        $allowedVisibilities = ['own', 'children', 'tree', 'tenant'];
        
        if (!in_array($visibility, $allowedVisibilities)) {
            throw new \InvalidArgumentException("Invalid visibility: {$visibility}");
        }

        $this->setSetting('organization_visibility', $visibility);
    }
}
