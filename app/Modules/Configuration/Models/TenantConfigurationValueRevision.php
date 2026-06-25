<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Configuration\Constants\ConfigurationScope;

final class TenantConfigurationValueRevision extends ConfigurationValueRevision
{
    protected $table = 'tenant_configuration_value_revisions';

    public function scopeName(): string
    {
        return ConfigurationScope::TENANT;
    }

    public function organizationUnitId(): ?int
    {
        return null;
    }
}
