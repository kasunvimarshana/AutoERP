<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Configuration\Constants\ConfigurationScope;

final class GlobalConfigurationValueRevision extends ConfigurationValueRevision
{
    protected $table = 'global_configuration_value_revisions';

    public function scopeName(): string
    {
        return ConfigurationScope::GLOBAL;
    }

    public function tenantId(): ?int
    {
        return null;
    }

    public function organizationUnitId(): ?int
    {
        return null;
    }
}
