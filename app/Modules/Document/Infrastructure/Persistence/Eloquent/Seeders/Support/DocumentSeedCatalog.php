<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class DocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'default';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentTypes(): array
    {
        return [
            ['name' => 'Document', 'code' => 'DOC', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['name' => 'Invoice', 'code' => 'INV', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['name' => 'Purchase Order', 'code' => 'PO', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['name' => 'Sales Order', 'code' => 'SO', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['name' => 'Service Job Card', 'code' => 'SVC', 'default_status' => 'open', 'is_active' => true, 'requires_source' => false],
            ['name' => 'Rental Agreement', 'code' => 'RNT', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemTypes(): array
    {
        return [
            ['name' => 'inventory', 'code' => 'inventory', 'display_name' => 'Inventory Item', 'is_active' => true],
            ['name' => 'service', 'code' => 'service', 'display_name' => 'Service Item', 'is_active' => true],
            ['name' => 'labour', 'code' => 'labour', 'display_name' => 'Labour Item', 'is_active' => true],
            ['name' => 'charge', 'code' => 'charge', 'display_name' => 'Charge Item', 'is_active' => true],
            ['name' => 'note', 'code' => 'note', 'display_name' => 'Note Item', 'is_active' => true],
            ['name' => 'rental', 'code' => 'rental', 'display_name' => 'Rental Item', 'is_active' => true],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function documentDefinitions(): array
    {
        return [
            'DOC' => [
                'name' => 'Default Document Definition',
                'header_schema' => [
                    'category' => ['required' => true],
                    'reference_number' => ['required' => false],
                ],
                'allowed_item_types' => ['inventory', 'service', 'labour', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'General', 'fields' => ['category', 'reference_number']],
                    ],
                ],
            ],
            'INV' => [
                'name' => 'Invoice Definition',
                'header_schema' => [
                    'category' => ['required' => true],
                    'customer_reference' => ['required' => false],
                    'billing_period' => ['required' => false],
                ],
                'allowed_item_types' => ['inventory', 'service', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'Invoice', 'fields' => ['category', 'customer_reference', 'billing_period']],
                    ],
                ],
            ],
            'PO' => [
                'name' => 'Purchase Order Definition',
                'header_schema' => [
                    'supplier_reference' => ['required' => false],
                    'expected_delivery_date' => ['required' => false],
                ],
                'allowed_item_types' => ['inventory', 'service', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'Purchase Order', 'fields' => ['supplier_reference', 'expected_delivery_date']],
                    ],
                ],
            ],
            'SO' => [
                'name' => 'Sales Order Definition',
                'header_schema' => [
                    'customer_reference' => ['required' => false],
                    'delivery_date' => ['required' => false],
                ],
                'allowed_item_types' => ['inventory', 'service', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'Sales Order', 'fields' => ['customer_reference', 'delivery_date']],
                    ],
                ],
            ],
            'SVC' => [
                'name' => 'Service Job Card Definition',
                'header_schema' => [
                    'reported_issue' => ['required' => true],
                    'vehicle_number' => ['required' => false],
                    'service_advisor' => ['required' => false],
                ],
                'allowed_item_types' => ['labour', 'inventory', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'Job Details', 'fields' => ['reported_issue', 'vehicle_number', 'service_advisor']],
                    ],
                ],
            ],
            'RNT' => [
                'name' => 'Rental Agreement Definition',
                'header_schema' => [
                    'start_date' => ['required' => true],
                    'end_date' => ['required' => false],
                    'asset_reference' => ['required' => false],
                    'billing_cycle' => ['required' => false],
                ],
                'allowed_item_types' => ['rental', 'charge', 'note'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'Rental', 'fields' => ['start_date', 'end_date', 'asset_reference', 'billing_cycle']],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function itemDefinitions(): array
    {
        return [
            'inventory' => [
                'name' => 'Inventory Item',
                'field_schema' => [
                    'quantity' => ['required' => true],
                    'unit_price' => ['required' => true],
                    'discount_amount' => ['required' => false],
                    'tax_amount' => ['required' => false],
                ],
                'validation_rules' => [
                    'quantity' => 'quantity > 0',
                    'unit_price' => 'unit_price >= 0',
                ],
                'calculation_rule' => '(quantity*unit_price)-discount_amount+tax_amount',
            ],
            'service' => [
                'name' => 'Service Item',
                'field_schema' => [
                    'hours' => ['required' => true],
                    'hourly_rate' => ['required' => true],
                ],
                'validation_rules' => [
                    'hours' => 'hours > 0',
                    'hourly_rate' => 'hourly_rate >= 0',
                ],
                'calculation_rule' => '(hours*hourly_rate)',
            ],
            'labour' => [
                'name' => 'Labour Item',
                'field_schema' => [
                    'hours' => ['required' => true],
                    'hourly_rate' => ['required' => true],
                ],
                'validation_rules' => [
                    'hours' => 'hours > 0',
                    'hourly_rate' => 'hourly_rate >= 0',
                ],
                'calculation_rule' => '(hours*hourly_rate)',
            ],
            'charge' => [
                'name' => 'Charge Item',
                'field_schema' => [
                    'amount' => ['required' => true],
                ],
                'validation_rules' => [
                    'amount' => 'amount >= 0',
                ],
                'calculation_rule' => 'amount',
            ],
            'note' => [
                'name' => 'Note Item',
                'field_schema' => [
                    'text' => ['required' => true],
                ],
                'validation_rules' => [],
                'calculation_rule' => '0',
            ],
            'rental' => [
                'name' => 'Rental Item',
                'field_schema' => [
                    'days' => ['required' => true],
                    'daily_rate' => ['required' => true],
                ],
                'validation_rules' => [
                    'days' => 'days > 0',
                    'daily_rate' => 'daily_rate >= 0',
                ],
                'calculation_rule' => '(days*daily_rate)',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function workflowBlueprints(): array
    {
        return [
            'DOC' => [
                'name' => 'Default Document Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'approved', 'display_name' => 'Approved', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'posted', 'display_name' => 'Posted', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'posted', 'action_name' => 'post'],
                ],
            ],
            'INV' => [
                'name' => 'Invoice Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'approved', 'display_name' => 'Approved', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'posted', 'display_name' => 'Posted', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'paid', 'display_name' => 'Paid', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'posted', 'action_name' => 'post'],
                    ['from' => 'posted', 'to' => 'paid', 'action_name' => 'settle'],
                ],
            ],
            'PO' => [
                'name' => 'Purchase Order Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'approved', 'display_name' => 'Approved', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'closed', 'display_name' => 'Closed', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'closed', 'action_name' => 'close'],
                ],
            ],
            'SO' => [
                'name' => 'Sales Order Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'approved', 'display_name' => 'Approved', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'fulfilled', 'display_name' => 'Fulfilled', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'fulfilled', 'action_name' => 'fulfil'],
                ],
            ],
            'SVC' => [
                'name' => 'Service Job Card Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'open', 'display_name' => 'Open', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'in_progress', 'display_name' => 'In Progress', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'completed', 'display_name' => 'Completed', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'closed', 'display_name' => 'Closed', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'open', 'to' => 'in_progress', 'action_name' => 'start'],
                    ['from' => 'in_progress', 'to' => 'completed', 'action_name' => 'complete'],
                    ['from' => 'completed', 'to' => 'closed', 'action_name' => 'close'],
                ],
            ],
            'RNT' => [
                'name' => 'Rental Agreement Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'active', 'display_name' => 'Active', 'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'completed', 'display_name' => 'Completed', 'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'active', 'action_name' => 'activate'],
                    ['from' => 'active', 'to' => 'completed', 'action_name' => 'complete'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function sequences(): array
    {
        return [
            ['document_type' => 'DOC', 'prefix' => 'DOC-', 'padding' => 5],
            ['document_type' => 'INV', 'prefix' => 'INV-', 'padding' => 5],
            ['document_type' => 'PO', 'prefix' => 'PO-', 'padding' => 5],
            ['document_type' => 'SO', 'prefix' => 'SO-', 'padding' => 5],
            ['document_type' => 'SVC', 'prefix' => 'SVC-', 'padding' => 5],
            ['document_type' => 'RNT', 'prefix' => 'RNT-', 'padding' => 5],
        ];
    }
}
