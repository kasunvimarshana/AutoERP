<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class VehicleRentalDocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'default';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentTypes(): array
    {
        return [
            [
                'name' => 'Vehicle Rental Agreement',
                'code' => 'VEHICLE_RENTAL_AGREEMENT',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => false,
            ],
            [
                'name' => 'Vehicle Rental Running Chart',
                'code' => 'VEHICLE_RENTAL_RUNNING_CHART',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
            [
                'name' => 'Vehicle Rental Invoice',
                'code' => 'VEHICLE_RENTAL_INVOICE',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
            [
                'name' => 'Vehicle Rental Replacement',
                'code' => 'VEHICLE_RENTAL_REPLACEMENT',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function documentDefinitionNames(): array
    {
        return [
            'VEHICLE_RENTAL_AGREEMENT' => 'Vehicle Rental Agreement Definition',
            'VEHICLE_RENTAL_RUNNING_CHART' => 'Vehicle Rental Running Chart Definition',
            'VEHICLE_RENTAL_INVOICE' => 'Vehicle Rental Invoice Definition',
            'VEHICLE_RENTAL_REPLACEMENT' => 'Vehicle Rental Replacement Definition',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function workflowBlueprints(): array
    {
        return [
            'VEHICLE_RENTAL_AGREEMENT' => [
                'name' => 'Vehicle Rental Agreement Workflow',
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
                        'name' => 'confirmed',
                        'display_name' => 'Confirmed',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'started',
                        'display_name' => 'Started',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'completed',
                        'display_name' => 'Completed',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 5,
                        'name' => 'closed',
                        'display_name' => 'Closed',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                    [
                        'sequence' => 6,
                        'name' => 'cancelled',
                        'display_name' => 'Cancelled',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'confirmed', 'action_name' => 'confirm'],
                    ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'confirmed', 'to' => 'started', 'action_name' => 'start'],
                    ['from' => 'confirmed', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'started', 'to' => 'completed', 'action_name' => 'complete'],
                    ['from' => 'started', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'completed', 'to' => 'closed', 'action_name' => 'close'],
                ],
            ],
            'VEHICLE_RENTAL_RUNNING_CHART' => [
                'name' => 'Vehicle Rental Running Chart Workflow',
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
                        'name' => 'submitted',
                        'display_name' => 'Submitted',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'approved',
                        'display_name' => 'Approved',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'invoiced',
                        'display_name' => 'Invoiced',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 5,
                        'name' => 'cancelled',
                        'display_name' => 'Cancelled',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
                    ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'submitted', 'to' => 'approved', 'action_name' => 'approve'],
                    ['from' => 'submitted', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'approved', 'to' => 'invoiced', 'action_name' => 'invoice'],
                    ['from' => 'approved', 'to' => 'cancelled', 'action_name' => 'cancel'],
                ],
            ],
            'VEHICLE_RENTAL_INVOICE' => [
                'name' => 'Vehicle Rental Invoice Workflow',
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
                        'name' => 'submitted',
                        'display_name' => 'Submitted',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'posted',
                        'display_name' => 'Posted',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'closed',
                        'display_name' => 'Closed',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
                    ['from' => 'submitted', 'to' => 'posted', 'action_name' => 'post'],
                    ['from' => 'posted', 'to' => 'closed', 'action_name' => 'close'],
                ],
            ],
            'VEHICLE_RENTAL_REPLACEMENT' => [
                'name' => 'Vehicle Rental Replacement Workflow',
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
                        'name' => 'confirmed',
                        'display_name' => 'Confirmed',
                        'is_initial' => false,
                        'is_terminal' => false,
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'completed',
                        'display_name' => 'Completed',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'cancelled',
                        'display_name' => 'Cancelled',
                        'is_initial' => false,
                        'is_terminal' => true,
                    ],
                ],
                'transitions' => [
                    ['from' => 'draft', 'to' => 'confirmed', 'action_name' => 'confirm'],
                    ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
                    ['from' => 'confirmed', 'to' => 'completed', 'action_name' => 'complete'],
                    ['from' => 'confirmed', 'to' => 'cancelled', 'action_name' => 'cancel'],
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
            ['document_type' => 'VEHICLE_RENTAL_AGREEMENT', 'prefix' => 'VRA-', 'padding' => 6],
            ['document_type' => 'VEHICLE_RENTAL_RUNNING_CHART', 'prefix' => 'VRC-', 'padding' => 6],
            ['document_type' => 'VEHICLE_RENTAL_INVOICE', 'prefix' => 'VRI-', 'padding' => 6],
            ['document_type' => 'VEHICLE_RENTAL_REPLACEMENT', 'prefix' => 'VRR-', 'padding' => 6],
        ];
    }
}
