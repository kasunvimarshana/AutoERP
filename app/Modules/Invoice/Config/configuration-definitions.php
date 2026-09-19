<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Invoice\Enums\InvoicePrintLayout;

return [
    InvoicePrintLayout::CONFIGURATION_KEY => [
        'owner' => 'Invoice',
        'version' => 1,
        'label' => 'Default invoice print layout',
        'description' => 'Paper layout used when printing invoices for this organization unit.',
        'type' => ConfigurationValueType::STRING,
        'default' => InvoicePrintLayout::StandardA4->value,
        'scopes' => [ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT],
        'options' => [
            InvoicePrintLayout::StandardA4->value,
            InvoicePrintLayout::CompactA5->value,
        ],
        'runtime_mutable' => true,
        'inherit_organization_hierarchy' => true,
        'sensitive' => false,
        'nullable' => false,
    ],
];
