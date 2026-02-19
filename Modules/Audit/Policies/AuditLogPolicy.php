<?php

declare(strict_types=1);

namespace Modules\Audit\Policies;

use Modules\Audit\Models\AuditLog;
use Modules\Auth\Models\User;

/**
 * AuditLog Policy
 *
 * Authorization for audit log access (admin/auditor roles only)
 */
class AuditLogPolicy
{
    /**
     * Determine if the user can view any audit logs
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    /**
     * Determine if the user can view the audit log
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        if (! $user->hasPermission('audit.view')) {
            return false;
        }

        // Ensure tenant isolation
        return $auditLog->tenant_id === $user->tenant_id;
    }

    /**
     * Determine if the user can export audit logs
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('audit.export');
    }

    /**
     * Determine if the user can view statistics
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }
}
