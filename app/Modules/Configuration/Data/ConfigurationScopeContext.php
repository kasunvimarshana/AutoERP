<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Data;

use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationScope;

final readonly class ConfigurationScopeContext
{
    public function __construct(
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
    ) {
        $valid = match ($scope) {
            ConfigurationScope::GLOBAL => $tenantId === null && $organizationUnitId === null,
            ConfigurationScope::TENANT => $tenantId !== null && $tenantId > 0 && $organizationUnitId === null,
            ConfigurationScope::ORGANIZATION_UNIT => $tenantId !== null
                && $tenantId > 0
                && $organizationUnitId !== null
                && $organizationUnitId > 0,
            default => false,
        };

        if (! $valid) {
            throw new InvalidArgumentException('The configuration scope identity is invalid.');
        }
    }
}
