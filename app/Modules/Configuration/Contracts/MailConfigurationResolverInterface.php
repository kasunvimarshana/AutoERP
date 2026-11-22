<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\Data\MailConfiguration;

interface MailConfigurationResolverInterface
{
    /**
     * Resolve effective mail configuration for a tenant and optional organization unit.
     */
    public function resolve(int $tenantId, ?int $organizationUnitId = null): MailConfiguration;

    /**
     * Resolve and apply effective mail configuration to the Laravel runtime mailer environment.
     */
    public function configureEnvironment(int $tenantId, ?int $organizationUnitId = null): MailConfiguration;
}
