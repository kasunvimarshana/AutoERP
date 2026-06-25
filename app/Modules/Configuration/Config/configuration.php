<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'definitions' => [
        'branding.display_name' => [
            'label' => 'Workspace display name',
            'description' => 'Human-readable name shown in tenant-facing documents and screens.',
            'owner' => 'Configuration',
            'version' => 1,
            'type' => ConfigurationValueType::STRING,
            'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
            'default' => env('APP_NAME', 'AutoERP'),
            'nullable' => false,
            'sensitive' => false,
            'runtime_mutable' => true,
        ],
        'localization.timezone' => [
            'label' => 'Workspace timezone',
            'description' => 'IANA timezone used when presenting local dates and times to users.',
            'owner' => 'Configuration',
            'version' => 1,
            'type' => ConfigurationValueType::STRING,
            'scopes' => [
                ConfigurationScope::GLOBAL,
                ConfigurationScope::TENANT,
                ConfigurationScope::ORGANIZATION_UNIT,
            ],
            'default' => 'UTC',
            'nullable' => false,
            'sensitive' => false,
            'runtime_mutable' => true,
            'lookup' => 'timezones',
        ],
    ],
];
