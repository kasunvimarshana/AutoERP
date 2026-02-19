<?php

declare(strict_types=1);

namespace Modules\Reporting\Policies;

use Modules\Auth\Models\User;

/**
 * AnalyticsPolicy
 *
 * Authorization policy for analytics access
 * Analytics data is sensitive and should be restricted
 */
class AnalyticsPolicy
{
    /**
     * Determine if user can view sales analytics
     */
    public function viewSales(User $user): bool
    {
        return $user->hasPermission('analytics.sales.view') ||
               $user->hasPermission('analytics.view') ||
               $user->hasRole('admin');
    }

    /**
     * Determine if user can view inventory analytics
     */
    public function viewInventory(User $user): bool
    {
        return $user->hasPermission('analytics.inventory.view') ||
               $user->hasPermission('analytics.view') ||
               $user->hasRole('admin');
    }

    /**
     * Determine if user can view CRM analytics
     */
    public function viewCrm(User $user): bool
    {
        return $user->hasPermission('analytics.crm.view') ||
               $user->hasPermission('analytics.view') ||
               $user->hasRole('admin');
    }

    /**
     * Determine if user can view financial analytics
     */
    public function viewFinancial(User $user): bool
    {
        return $user->hasPermission('analytics.financial.view') ||
               $user->hasPermission('analytics.view') ||
               $user->hasRole('admin');
    }

    /**
     * Determine if user can view analytics for a specific organization
     */
    public function viewOrganization(User $user, ?int $organizationId = null): bool
    {
        // Super admins and users with global analytics permission can view all
        if ($user->hasRole('super-admin') || $user->hasPermission('analytics.view-all-organizations')) {
            return true;
        }

        // If no specific organization requested, user can view their own
        if ($organizationId === null) {
            return true;
        }

        // Check if user belongs to the requested organization
        return $user->organization_id === $organizationId ||
               $user->hasAccessToOrganization($organizationId);
    }
}
