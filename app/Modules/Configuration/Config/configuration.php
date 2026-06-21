<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'definitions' => [
        'app.name' => [
            'label' => 'Application name',
            'description' => 'Human-readable product or tenant-facing application name.',
            'owner' => 'Configuration',
            'type' => ConfigurationValueType::STRING,
            'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
            'default' => env('APP_NAME', 'AutoERP'),
            'nullable' => false,
            'sensitive' => false,
            'runtime_mutable' => true,
        ],
        'app.timezone' => [
            'label' => 'Default timezone',
            'description' => 'IANA timezone used when displaying local dates and times.',
            'owner' => 'Configuration',
            'type' => ConfigurationValueType::STRING,
            'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT],
            'default' => 'UTC',
            'nullable' => false,
            'sensitive' => false,
            'runtime_mutable' => true,
            'lookup' => 'timezones',
        ],
    ],
];
