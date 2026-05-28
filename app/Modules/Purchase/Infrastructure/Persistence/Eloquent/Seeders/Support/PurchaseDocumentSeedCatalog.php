<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class PurchaseDocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'default';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentTypes(): array
    {
        return [
            [
                'name' => 'Purchase Order',
                'code' => 'PURCHASE_ORDER',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
            [
                'name' => 'Goods Received Note',
                'code' => 'GOODS_RECEIVED_NOTE',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
            [
                'name' => 'Purchase Invoice',
                'code' => 'PURCHASE_INVOICE',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
            [
                'name' => 'Purchase Return',
                'code' => 'PURCHASE_RETURN',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemTypes(): array
    {
        return [
            [
                'name' => 'purchase_line',
                'code' => 'purchase_line',
                'display_name' => 'Purchase Line',
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function documentDefinitions(): array
    {
        return [
            'PURCHASE_ORDER' => [
                'name' => 'Purchase Order Definition',
                'header_schema' => [
                    'supplier_id' => ['required' => true, 'type' => 'number'],
                    'warehouse_id' => ['required' => true, 'type' => 'number'],
                    'order_date' => ['required' => true, 'type' => 'date'],
                    'expected_date' => ['required' => false, 'type' => 'date'],
                    'currency_id' => ['required' => true, 'type' => 'number'],
                    'price_list_id' => ['required' => false, 'type' => 'number'],
                    'reference' => ['required' => false, 'type' => 'text'],
                ],
                'allowed_item_types' => ['purchase_line'],
            ],
            'GOODS_RECEIVED_NOTE' => [
                'name' => 'GRN Definition',
                'header_schema' => [
                    'supplier_id' => ['required' => true, 'type' => 'number'],
                    'warehouse_id' => ['required' => true, 'type' => 'number'],
                    'received_date' => ['required' => true, 'type' => 'date'],
                    'purchase_order_id' => ['required' => false, 'type' => 'number'],
                    'currency_id' => ['required' => true, 'type' => 'number'],
                    'reference' => ['required' => false, 'type' => 'text'],
                ],
                'allowed_item_types' => ['purchase_line'],
            ],
            'PURCHASE_INVOICE' => [
                'name' => 'Purchase Invoice Definition',
                'header_schema' => [
                    'supplier_id' => ['required' => true, 'type' => 'number'],
                    'invoice_date' => ['required' => true, 'type' => 'date'],
                    'due_date' => ['required' => false, 'type' => 'date'],
                    'currency_id' => ['required' => true, 'type' => 'number'],
                    'reference' => ['required' => false, 'type' => 'text'],
                ],
                'allowed_item_types' => ['purchase_line'],
            ],
            'PURCHASE_RETURN' => [
                'name' => 'Purchase Return Definition',
                'header_schema' => [
                    'supplier_id' => ['required' => true, 'type' => 'number'],
                    'return_date' => ['required' => true, 'type' => 'date'],
                    'original_document_id' => ['required' => false, 'type' => 'number'],
                    'is_without_original' => ['required' => false, 'type' => 'boolean'],
                    'currency_id' => ['required' => true, 'type' => 'number'],
                    'reason' => ['required' => false, 'type' => 'text'],
                ],
                'allowed_item_types' => ['purchase_line'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function workflowBlueprints(): array
    {
        return [
            'PURCHASE_ORDER' => [
                'name' => 'Purchase Order Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true,
                        'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'submitted', 'display_name' => 'Submitted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'approved', 'display_name' => 'Approved',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'documented', 'display_name' => 'Documented',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 5, 'name' => 'cancelled', 'display_name' => 'Cancelled',
                        'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
                    ['from' => 'submitted', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'documented', 'action_name' => 'document'],
                    ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'submitted', 'to' => 'cancelled', 'action_name' => 'cancel'],
                ],
            ],
            'GOODS_RECEIVED_NOTE' => [
                'name' => 'GRN Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true,
                        'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'confirmed', 'display_name' => 'Confirmed',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'posted', 'display_name' => 'Posted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'documented', 'display_name' => 'Documented',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 5, 'name' => 'cancelled', 'display_name' => 'Cancelled',
                        'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'confirmed', 'action_name' => 'confirm'],
                    ['from' => 'confirmed', 'to' => 'posted', 'action_name' => 'post'],
                    ['from' => 'posted', 'to' => 'documented', 'action_name' => 'document'],
                    ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
                ],
            ],
            'PURCHASE_INVOICE' => [
                'name' => 'Purchase Invoice Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true,
                        'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'submitted', 'display_name' => 'Submitted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'posted', 'display_name' => 'Posted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'closed', 'display_name' => 'Closed',
                        'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
                    ['from' => 'submitted', 'to' => 'posted', 'action_name' => 'post'],
                    ['from' => 'posted', 'to' => 'closed', 'action_name' => 'close'],
                ],
            ],
            'PURCHASE_RETURN' => [
                'name' => 'Purchase Return Workflow',
                'steps' => [
                    ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true,
                        'is_terminal' => false],
                    ['sequence' => 2, 'name' => 'submitted', 'display_name' => 'Submitted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 3, 'name' => 'approved', 'display_name' => 'Approved',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 4, 'name' => 'posted', 'display_name' => 'Posted',
                        'is_initial' => false, 'is_terminal' => false],
                    ['sequence' => 5, 'name' => 'closed', 'display_name' => 'Closed',
                        'is_initial' => false, 'is_terminal' => true],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
                    ['from' => 'submitted', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'approved', 'to' => 'posted', 'action_name' => 'post'],
                    ['from' => 'posted', 'to' => 'closed', 'action_name' => 'close'],
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
            ['document_type' => 'PURCHASE_ORDER', 'prefix' => 'PO-', 'padding' => 6],
            ['document_type' => 'GOODS_RECEIVED_NOTE', 'prefix' => 'GRN-', 'padding' => 6],
            ['document_type' => 'PURCHASE_INVOICE', 'prefix' => 'PINV-', 'padding' => 6],
            ['document_type' => 'PURCHASE_RETURN', 'prefix' => 'PRET-', 'padding' => 6],
        ];
    }
}
