<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Architecture Mode
    |--------------------------------------------------------------------------
    |
    | Choose the DDD architecture layout for your application.
    |
    |   "layered"  — Classic DDD: src/{Context}/{Domain,Application,Infrastructure}
    |   "modular"  — Modular DDD: app/Modules/{Context}/{Domain,Application,Infrastructure}
    |   "flat"     — All contexts live directly under a single base path
    |
    */
    'mode' => env('DDD_MODE', 'layered'),

    /*
    |--------------------------------------------------------------------------
    | Base Paths Per Mode
    |--------------------------------------------------------------------------
    |
    | Root filesystem path where bounded contexts are generated.
    | Supports multiple modes — the active mode key is used at runtime.
    |
    */
    'paths' => [
        'layered'  => base_path('src'),
        'modular'  => base_path('app/Modules'),
        'flat'     => base_path('app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Roots Per Mode
    |--------------------------------------------------------------------------
    |
    | PSR-4 namespace prefix matching your composer.json autoload section.
    |
    */
    'namespaces' => [
        'layered'  => 'App',
        'modular'  => 'App\\Modules',
        'flat'     => 'App',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Kernel Path & Namespace
    |--------------------------------------------------------------------------
    |
    | The Shared Kernel holds cross-cutting contracts, value objects, and
    | base abstractions. It is scaffolded once on first context creation.
    |
    */
    'shared_kernel' => [
        'path'      => base_path('src/Shared'),
        'namespace' => 'App\\Shared',
        'auto_scaffold' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Layer Directory Names
    |--------------------------------------------------------------------------
    |
    | Rename any layer directory here. All generators read this config.
    |
    */
    'layers' => [
        'domain'         => 'Domain',
        'application'    => 'Application',
        'infrastructure' => 'Infrastructure',
        'presentation'   => 'Presentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Sub-Directories
    |--------------------------------------------------------------------------
    */
    'domain_directories' => [
        'entities'       => 'Entities',
        'value_objects'  => 'ValueObjects',
        'aggregates'     => 'Aggregates',
        'events'         => 'Events',
        'exceptions'     => 'Exceptions',
        'factories'      => 'Factories',
        'repositories'   => 'Repositories',
        'services'       => 'Services',
        'policies'       => 'Policies',
        'specifications' => 'Specifications',
        'enums'          => 'Enums',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Sub-Directories
    |--------------------------------------------------------------------------
    */
    'application_directories' => [
        'commands'    => 'Commands',
        'queries'     => 'Queries',
        'handlers'    => 'Handlers',
        'dtos'        => 'DTOs',
        'services'    => 'Services',
        'use_cases'   => 'UseCases',
        'mappers'     => 'Mappers',
        'validators'  => 'Validators',
    ],

    /*
    |--------------------------------------------------------------------------
    | Infrastructure Sub-Directories
    |--------------------------------------------------------------------------
    */
    'infrastructure_directories' => [
        'persistence'     => 'Persistence',
        'eloquent'        => 'Persistence/Eloquent',
        'repositories'    => 'Persistence/Repositories',
        'migrations'      => 'Persistence/Migrations',
        'factories'       => 'Persistence/Factories',
        'seeders'         => 'Persistence/Seeders',
        'casts'           => 'Persistence/Casts',
        'services'        => 'Services',
        'integrations'    => 'Integrations',
        'events'          => 'Events',
        'jobs'            => 'Jobs',
        'notifications'   => 'Notifications',
        'providers'       => 'Providers',
        'logging'         => 'Logging',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presentation Sub-Directories
    |--------------------------------------------------------------------------
    */
    'presentation_directories' => [
        'controllers_api' => 'Http/Controllers/Api',
        'controllers_web' => 'Http/Controllers/Web',
        'requests'        => 'Http/Requests',
        'resources'       => 'Http/Resources',
        'middleware'      => 'Http/Middleware',
        'routes'          => 'Http/Routes',
        'views'           => 'Views',
        'commands'        => 'Console/Commands',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the ServiceProvider will scan each bounded context
    | directory and auto-register any {Context}ServiceProvider it finds.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Provider Pattern
    |--------------------------------------------------------------------------
    |
    | The fully-qualified class name pattern for auto-discovered providers.
    | Available tokens: {namespace}, {context}
    |
    */
    'provider_pattern' => '{namespace}\\{context}\\Infrastructure\\Providers\\{context}ServiceProvider',

    /*
    |--------------------------------------------------------------------------
    | Stub Paths (in order of preference)
    |--------------------------------------------------------------------------
    |
    | The package resolves stubs in the order listed. Custom stubs published
    | to resource_path('stubs/ddd') take priority over package defaults.
    |
    */
    'stub_paths' => [
        resource_path('stubs/ddd'),       // User-published (highest priority)
        base_path('stubs/ddd'),           // Project-level overrides
        // Package built-ins resolved automatically by StubRenderer
    ],

    /*
    |--------------------------------------------------------------------------
    | File Generator
    |--------------------------------------------------------------------------
    */
    'generator' => [
        'backup_on_overwrite' => false,    // Create .bak before overwriting
        'dry_run'             => false,    // Preview without writing
    ],

    /*
    |--------------------------------------------------------------------------
    | Console Output
    |--------------------------------------------------------------------------
    */
    'output' => [
        'verbose'    => false,
        'timestamps' => false,
    ],

];
