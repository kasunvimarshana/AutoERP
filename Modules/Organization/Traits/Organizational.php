<?php

declare(strict_types=1);

namespace Modules\Organization\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Entities\Location;
use Modules\Organization\Entities\Organization;

/**
 * Organizational Trait
 *
 * Provides organizational context (organization and location) to entities.
 * Use this trait on any entity that belongs to an organization and/or location.
 */
trait Organizational
{
    /**
     * Boot the organizational trait for a model.
     */
    public static function bootOrganizational(): void
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
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope query to a specific location.
     */
    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope query to multiple organizations.
     */
    public function scopeForOrganizations($query, array $organizationIds)
    {
        return $query->whereIn('organization_id', $organizationIds);
    }

    /**
     * Scope query to multiple locations.
     */
    public function scopeForLocations($query, array $locationIds)
    {
        return $query->whereIn('location_id', $locationIds);
    }
}
