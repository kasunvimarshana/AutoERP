<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Invoice\Contracts\InvoiceLayoutResolverInterface;
use Modules\Invoice\Enums\InvoicePrintLayout;
use Modules\Invoice\Services\InvoiceLayoutProvisioner;

return [
    InvoiceLayoutResolverInterface::DEFAULT_LAYOUT_KEY => [
        'owner' => 'Invoice',
        'version' => 1,
        'label' => 'Default invoice layout template',
        'description' => 'Layout template used when generating or printing invoices.',
        'type' => ConfigurationValueType::STRING,
        'default' => InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4,
        'scopes' => [ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT],
        'options' => [],
        'runtime_mutable' => true,
        'inherit_organization_hierarchy' => true,
        'sensitive' => false,
        'nullable' => false,
    ],
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
            InvoicePrintLayout::Continuous85By55->value,
        ],
        'runtime_mutable' => true,
        'inherit_organization_hierarchy' => true,
        'sensitive' => false,
        'nullable' => false,
    ],
];
