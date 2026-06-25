<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'resolution' => [
        'signals' => [
            'id_input_keys' => ['organization_unit_id'],
            'id_route_keys' => ['organization_unit_id', 'organization_unit'],
            'id_header_keys' => ['X-Organization-Unit-Id', 'X-Organization-Unit'],
            'code_input_keys' => ['organization_unit_code'],
            'code_route_keys' => ['organization_unit_code'],
            'code_header_keys' => ['X-Organization-Unit-Code'],
            'path_input_keys' => ['organization_unit_path'],
            'path_header_keys' => ['X-Organization-Unit-Path'],
            'name_input_keys' => ['organization_unit_name'],
            'name_header_keys' => ['X-Organization-Unit-Name'],
        ],
    ],
];
