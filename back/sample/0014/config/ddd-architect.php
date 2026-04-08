<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Structure Preset
    |--------------------------------------------------------------------------
    | Select which DDD structure preset to use when scaffolding contexts.
    | Supported: "ddd-layered", "ddd-modular", "ddd-hexagonal", "custom"
    */
    'structure' => env('DDD_STRUCTURE', 'ddd-layered'),

    /*
    |--------------------------------------------------------------------------
    | Structure Presets
    |--------------------------------------------------------------------------
    | Named presets that override root-level config when selected.
    | Add your own — the package picks them up automatically.
    */
    'structure_choices' => [

        // Classic layered DDD (default) — shared Infrastructure + Presentation
        'ddd-layered' => [
            'base_path' => 'app',
            'namespace' => 'App',
            'mode'      => 'full',
        ],

        // Modular DDD — each context is fully self-contained (nWidart-style, no module system)
        'ddd-modular' => [
            'base_path' => 'src',
            'namespace' => 'Src',
            'mode'      => 'full',
            'domain_structure' => [
                'Domain/Aggregates', 'Domain/Entities', 'Domain/ValueObjects',
                'Domain/Events',     'Domain/Exceptions','Domain/Factories',
                'Domain/Repositories','Domain/Services', 'Domain/Policies',
            ],
            'application_structure' => [
                'Application/Commands', 'Application/Handlers',
                'Application/Queries',  'Application/DTOs', 'Application/Services',
            ],
            'infrastructure_structure' => [
                'Infrastructure/Persistence/Eloquent',
                'Infrastructure/Persistence/Repositories',
                'Infrastructure/Persistence/Migrations',
                'Infrastructure/Persistence/Factories',
                'Infrastructure/Persistence/Seeders',
                'Infrastructure/ExternalApis',
                'Infrastructure/Messaging',
                'Infrastructure/Concerns',
            ],
            'presentation_structure' => [
                'Interface/Controllers', 'Interface/Requests',
                'Interface/Resources',   'Interface/Middleware', 'Interface/Routes',
            ],
        ],

        // Hexagonal / Ports & Adapters
        'ddd-hexagonal' => [
            'base_path' => 'app',
            'namespace' => 'App',
            'mode'      => 'full',
            'domain_structure' => [
                'Core/Entities',  'Core/ValueObjects', 'Core/Aggregates',
                'Core/Events',    'Core/Exceptions',   'Core/Specifications',
                'Ports/Inbound',  'Ports/Outbound',
            ],
            'application_structure' => [
                'UseCases/Commands', 'UseCases/Queries',
                'UseCases/Handlers', 'UseCases/DTOs',
            ],
            'infrastructure_structure' => [
                'Adapters/Persistence/Eloquent',
                'Adapters/Persistence/Repositories',
                'Adapters/Persistence/Migrations',
                'Adapters/Persistence/Factories',
                'Adapters/Persistence/Seeders',
                'Adapters/Http/Controllers',
                'Adapters/Http/Requests',
                'Adapters/Http/Resources',
                'Adapters/Http/Routes',
                'Adapters/Messaging',
                'Adapters/ExternalApis',
            ],
            'presentation_structure' => [],
        ],

        // Custom — falls back to root-level keys below
        'custom' => [],
    ],

    'base_path' => 'app',
    'namespace'  => 'App',
    'mode'       => 'full',

    'layers' => ['domain', 'application', 'infrastructure', 'presentation'],

    'shared_kernel'  => true,
    'auto_discover'  => true,
    'provider_pattern' => '{{Context}}ServiceProvider',

    'domain_structure' => [
        'Entities', 'ValueObjects', 'Aggregates', 'Repositories',
        'Services',  'Events',       'Exceptions',  'Policies',
        'Enums',     'Specifications','Factories',
    ],

    'application_structure' => [
        'DTOs', 'UseCases', 'Commands', 'Queries',
        'Handlers', 'Mappers', 'Validators', 'Services',
    ],

    'infrastructure_structure' => [
        'Persistence/Eloquent',     'Persistence/Repositories',
        'Persistence/Migrations',   'Persistence/Factories',
        'Persistence/Seeders',      'Persistence/Casts',
        'Services', 'Integrations', 'Events', 'Jobs',
        'Notifications', 'Providers', 'Logging',
    ],

    'presentation_structure' => [
        'Http/Controllers/Api', 'Http/Controllers/Web',
        'Http/Requests',        'Http/Resources',
        'Http/Middleware',      'Http/Exceptions',
        'Http/Routes',          'Console/Commands', 'Views',
    ],

    'shared_structure' => [
        'Domain/Shared/Contracts',  'Domain/Shared/ValueObjects',
        'Domain/Shared/Exceptions', 'Domain/Shared/Traits',
        'Domain/Shared/Events',     'Application/Shared/DTOs',
        'Application/Shared/Contracts', 'Application/Shared/Traits',
        'Application/Shared/Exceptions',
    ],

    'test_structure'    => ['Unit', 'Feature'],
    'stub_path'         => resource_path('stubs/ddd'),
    'generate_gitkeep'  => true,
    'generate_readme'   => false,
    'route_files'       => ['api.php', 'web.php'],
    'command_prefix'    => 'ddd',
];
