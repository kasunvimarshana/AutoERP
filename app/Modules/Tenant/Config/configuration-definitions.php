<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'tenant.policy.cross_organization_transactions' => [
        'label' => 'Cross-organization transactions',
        'description' => 'Allow authorized workflows to transact across organization units within this tenant.',
        'owner' => 'Tenant',
        'version' => 1,
        'type' => ConfigurationValueType::BOOLEAN,
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
        'default' => false,
        'nullable' => false,
        'sensitive' => false,
        'runtime_mutable' => true,
    ],
];
