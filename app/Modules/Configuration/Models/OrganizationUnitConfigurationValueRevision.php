<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Configuration\Constants\ConfigurationScope;

final class OrganizationUnitConfigurationValueRevision extends ConfigurationValueRevision
{
    protected $table = 'organization_unit_configuration_value_revisions';

    public function scopeName(): string
    {
        return ConfigurationScope::ORGANIZATION_UNIT;
    }
}
