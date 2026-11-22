<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

interface OrganizationUnitAuthScopeRevokerInterface
{
    /** Revoke active auth artifacts bound to a membership that is being removed. */
    public function revokeForUserOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): void;
}
