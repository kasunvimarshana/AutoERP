<?php

declare(strict_types=1);

use Modules\Auth\Constants\RegistrationMode;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'auth.registration_mode' => [
        'owner' => 'Auth',
        'version' => 2,
        'label' => 'User registration policy',
        'description' => 'Controls whether tenant users may complete administrator-issued invitations.',
        'type' => ConfigurationValueType::STRING,
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT],
        'default' => RegistrationMode::INVITE_ONLY,
        'options' => RegistrationMode::values(),
        'nullable' => false,
        'sensitive' => false,
        'runtime_mutable' => true,
        'inherit_organization_hierarchy' => false,
    ],
];
