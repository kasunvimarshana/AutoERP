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
            [
                'name' => 'Generic Document',
                'code' => 'GENERIC',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => false,
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
        return [
            'GENERIC' => [
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
            ],
        ];
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
        ];
    }
}
