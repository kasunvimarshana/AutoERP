<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\StockLedger;

/**
 * StockLedger authorization policy
 * 
 * Stock ledgers are append-only with strict immutability rules.
 * Only admins can delete/modify entries for data correction purposes.
 */
class StockLedgerPolicy
{
    /**
     * Determine whether the user can view any stock ledger entries.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view inventory
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-inventory');
    }

    /**
     * Determine whether the user can view the stock ledger entry.
     * 
     * Enforces tenant isolation and branch-level restrictions.
     * 
     * @param User $user The authenticated user
     * @param StockLedger $stockLedger The stock ledger entry to view
     * @return bool True if the user can view this entry
     */
    public function view(User $user, StockLedger $stockLedger): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $stockLedger->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-inventory')) {
            return false;
        }

        // Super admins can view all entries in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Branch-level users can only view their branch entries
        if ($user->branch_id && $stockLedger->branch_id) {
            return $user->branch_id === $stockLedger->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create stock ledger entries.
     * 
     * Note: Creation is typically done through stock operations (stock in, out, etc.)
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create entries
     */
    public function create(User $user): bool
    {
        // Users with any stock operation permission can create entries
        return $user->can('stock-in') 
            || $user->can('stock-out') 
            || $user->can('stock-adjustment') 
            || $user->can('stock-transfer');
    }

    /**
     * Determine whether the user can update the stock ledger entry.
     * 
     * Stock ledger is append-only. Updates are NOT allowed except by super admins
     * for critical data corrections.
     * 
     * @param User $user The authenticated user
     * @param StockLedger $stockLedger The stock ledger entry to update
     * @return bool True if the user can update this entry
     */
    public function update(User $user, StockLedger $stockLedger): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $stockLedger->tenant_id) {
            return false;
        }

        // Only super admins can update stock ledger entries (for critical corrections)
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        // Must have inventory management permission
        return $user->can('manage-inventory');
    }

    /**
     * Determine whether the user can delete the stock ledger entry.
     * 
     * Stock ledger is append-only. Deletes are NOT allowed except by super admins
     * for critical data corrections or erroneous entries.
     * 
     * @param User $user The authenticated user
     * @param StockLedger $stockLedger The stock ledger entry to delete
     * @return bool True if the user can delete this entry
     */
    public function delete(User $user, StockLedger $stockLedger): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $stockLedger->tenant_id) {
            return false;
        }

        // Only super admins and admins can delete entries
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        // Must have inventory management permission
        return $user->can('manage-inventory');
    }

    /**
     * Determine whether the user can restore the stock ledger entry.
     * 
     * @param User $user The authenticated user
     * @param StockLedger $stockLedger The stock ledger entry to restore
     * @return bool True if the user can restore this entry
     */
    public function restore(User $user, StockLedger $stockLedger): bool
    {
        // Same rules as delete
        return $this->delete($user, $stockLedger);
    }

    /**
     * Determine whether the user can permanently delete the stock ledger entry.
     * 
     * Force delete is extremely restricted - only super admins.
     * 
     * @param User $user The authenticated user
     * @param StockLedger $stockLedger The stock ledger entry to permanently delete
     * @return bool True if the user can permanently delete this entry
     */
    public function forceDelete(User $user, StockLedger $stockLedger): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $stockLedger->tenant_id) {
            return false;
        }

        // Only super admins can force delete
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('manage-inventory');
    }

    /**
     * Determine whether the user can perform stock in operations.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can perform stock in
     */
    public function stockIn(User $user): bool
    {
        return $user->can('stock-in');
    }

    /**
     * Determine whether the user can perform stock out operations.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can perform stock out
     */
    public function stockOut(User $user): bool
    {
        return $user->can('stock-out');
    }

    /**
     * Determine whether the user can perform stock adjustments.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can perform adjustments
     */
    public function stockAdjustment(User $user): bool
    {
        return $user->can('stock-adjustment');
    }

    /**
     * Determine whether the user can perform stock transfers.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can perform transfers
     */
    public function stockTransfer(User $user): bool
    {
        return $user->can('stock-transfer');
    }

    /**
     * Determine whether the user can manage inventory.
     * 
     * Full inventory management includes corrections, adjustments, and reporting.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can manage inventory
     */
    public function manage(User $user): bool
    {
        return $user->can('manage-inventory');
    }
}
