<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'auth.registration_mode' => [
        'owner' => 'Auth',
        'label' => 'User registration policy',
        'description' => 'Controls whether users may self-register, require invitations, or must use approved email domains.',
        'type' => ConfigurationValueType::STRING,
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
        'default' => 'invite_only',
        'options' => ['disabled', 'invite_only', 'approved_domains', 'open'],
        'nullable' => false,
        'sensitive' => false,
        'runtime_mutable' => true,
    ],
    'auth.registration_approved_domains' => [
        'owner' => 'Auth',
        'label' => 'Approved registration email domains',
        'description' => 'Email domains allowed when the registration policy is set to approved domains.',
        'type' => ConfigurationValueType::JSON,
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
        'default' => [],
        'nullable' => false,
        'sensitive' => false,
        'runtime_mutable' => true,
    ],
];
