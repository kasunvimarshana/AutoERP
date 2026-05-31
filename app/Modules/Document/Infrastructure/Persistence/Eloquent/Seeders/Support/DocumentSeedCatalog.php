<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class DocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'AUTOERP';

    public static function defaultTenantCode(): string
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', self::DEFAULT_TENANT_CODE)));

        return $code !== '' ? $code : self::DEFAULT_TENANT_CODE;
    }

    public static function defaultTenantName(): string
    {
        $name = trim((string) env('AUTH_LOCAL_TENANT_NAME', 'Default Tenant'));

        return $name !== '' ? $name : 'Default Tenant';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentTypes(): array
    {
        $types = [
            ['Generic Document', 'GENERIC', false],
            ['Purchase Invoice', 'purchase_invoice', true],
            ['Sales Invoice', 'sales_invoice', true],
            ['Service Invoice', 'service_invoice', true],
            ['Rental Invoice', 'rental_invoice', true],
            ['Job Card', 'job_card', true],
            ['Rental Agreement', 'rental_agreement', true],
            ['Voucher', 'voucher', true],
            ['Receipt', 'receipt', true],
        ];

        return array_map(static fn (array $type): array => [
            'name' => $type[0],
            'code' => $type[1],
            'default_status' => 'draft',
            'is_active' => true,
            'requires_source' => $type[2],
            'supports_items' => true,
            'supports_attachments' => true,
            'supports_comments' => true,
            'supports_versions' => true,
            'supports_workflow' => true,
        ], $types);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemTypes(): array
    {
        return [
            [
                'name' => 'generic_line',
                'code' => 'generic_line',
                'display_name' => 'Generic Line',
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function documentDefinitions(): array
    {
        $definition = [
                'name' => 'Generic Definition',
                'header_schema' => [
                    'title' => ['required' => true],
                    'external_reference' => ['required' => false],
                ],
                'allowed_item_types' => ['generic_line'],
                'validation_rules' => [],
                'form_layout' => [
                    'sections' => [
                        ['title' => 'General', 'fields' => ['title', 'external_reference']],
                    ],
                ],
        ];

        return array_fill_keys([
            'GENERIC',
            'purchase_invoice',
            'sales_invoice',
            'service_invoice',
            'rental_invoice',
            'job_card',
            'rental_agreement',
            'voucher',
            'receipt',
        ], $definition);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function itemDefinitions(): array
    {
        return [
            'generic_line' => [
                'name' => 'Generic Line Definition',
                'field_schema' => [
                    'label' => ['required' => true],
                    'line_total' => ['required' => true],
                ],
                'validation_rules' => [
                    'line_total' => 'line_total >= 0',
                ],
                'calculation_rule' => null,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function workflowBlueprints(): array
    {
        return [
            'GENERIC' => [
                'name' => 'Generic Workflow',
                'steps' => [
                    [
                        'sequence' => 1,
                        'name' => 'draft',
                        'display_name' => 'Draft',
                        'is_initial' => true,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'active',
                        'display_name' => 'Active',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'archived',
                        'display_name' => 'Archived',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'active', 'action_name' => 'activate'],
                    ['from' => 'active', 'to' => 'archived', 'action_name' => 'archive'],
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
            ['document_type' => 'GENERIC', 'prefix' => 'DOC-', 'padding' => 5],
            ['document_type' => 'purchase_invoice', 'prefix' => 'PINV-', 'padding' => 5],
            ['document_type' => 'sales_invoice', 'prefix' => 'SINV-', 'padding' => 5],
            ['document_type' => 'service_invoice', 'prefix' => 'SERVINV-', 'padding' => 5],
            ['document_type' => 'rental_invoice', 'prefix' => 'RENTINV-', 'padding' => 5],
            ['document_type' => 'job_card', 'prefix' => 'JOB-', 'padding' => 5],
            ['document_type' => 'rental_agreement', 'prefix' => 'RAGR-', 'padding' => 5],
            ['document_type' => 'voucher', 'prefix' => 'VCH-', 'padding' => 5],
            ['document_type' => 'receipt', 'prefix' => 'RCT-', 'padding' => 5],
        ];
    }
}
