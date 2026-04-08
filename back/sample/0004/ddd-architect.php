<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Module Base Path
    |--------------------------------------------------------------------------
    | The base path where DDD modules/contexts will be created.
    | This is relative to the Laravel application's base_path().
    |
    */
    'base_path' => 'app',

    /*
    |--------------------------------------------------------------------------
    | Default Namespace Root
    |--------------------------------------------------------------------------
    | The root namespace under which all bounded contexts are placed.
    | Mirrors PSR-4 autoload mapping in composer.json.
    |
    */
    'namespace' => 'App',

    /*
    |--------------------------------------------------------------------------
    | DDD Architecture Mode
    |--------------------------------------------------------------------------
    | Choose the structural layout that best suits your project.
    |
    | Supported values:
    |   "full"      - Creates all DDD layers: Domain, Application, Infrastructure, Presentation
    |   "domain"    - Only Domain layer (Entities, ValueObjects, Repositories, etc.)
    |   "minimal"   - Domain + Application (no Infrastructure / Presentation scaffolding)
    |   "custom"    - Uses the "layers" array below to pick exactly what to generate
    |
    */
    'mode' => 'full',

    /*
    |--------------------------------------------------------------------------
    | Custom Layer Selection (mode = "custom")
    |--------------------------------------------------------------------------
    | When 'mode' is 'custom', define which top-level layers to generate.
    |
    */
    'layers' => [
        'domain',
        'application',
        'infrastructure',
        'presentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Kernel
    |--------------------------------------------------------------------------
    | Whether to scaffold a Shared/ directory alongside bounded contexts.
    | The shared kernel contains cross-cutting ValueObjects, Contracts, Events.
    |
    */
    'shared_kernel' => true,

    /*
    |--------------------------------------------------------------------------
    | Context Discovery
    |--------------------------------------------------------------------------
    | Automatically register each bounded context's ServiceProvider on boot.
    | This scans the module base path and loads providers from Infrastructure/Providers.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Provider Naming Convention
    |--------------------------------------------------------------------------
    | The expected ServiceProvider class name within each context.
    | {{Context}} is replaced with the bounded context name at runtime.
    |
    */
    'provider_pattern' => '{{Context}}ServiceProvider',

    /*
    |--------------------------------------------------------------------------
    | Domain Layer Structure
    |--------------------------------------------------------------------------
    | Directories scaffolded inside Domain/{Context}/.
    |
    */
    'domain_structure' => [
        'Entities',
        'ValueObjects',
        'Aggregates',
        'Repositories',       // Interfaces / contracts only
        'Services',           // Pure domain services
        'Events',
        'Exceptions',
        'Policies',
        'Enums',
        'Specifications',
        'Factories',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Layer Structure
    |--------------------------------------------------------------------------
    | Directories scaffolded inside Application/{Context}/.
    |
    */
    'application_structure' => [
        'DTOs',
        'UseCases',
        'Commands',
        'Queries',
        'Handlers',
        'Mappers',
        'Validators',
        'Services',
    ],

    /*
    |--------------------------------------------------------------------------
    | Infrastructure Layer Structure
    |--------------------------------------------------------------------------
    | Directories scaffolded inside Infrastructure/.
    |
    */
    'infrastructure_structure' => [
        'Persistence/Eloquent',
        'Persistence/Repositories',
        'Persistence/Migrations',
        'Persistence/Factories',
        'Persistence/Seeders',
        'Persistence/Casts',
        'Services',
        'Integrations',
        'Events',
        'Jobs',
        'Notifications',
        'Providers',
        'Logging',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presentation Layer Structure
    |--------------------------------------------------------------------------
    | Directories scaffolded inside Presentation/.
    |
    */
    'presentation_structure' => [
        'Http/Controllers/Api',
        'Http/Controllers/Web',
        'Http/Requests',
        'Http/Resources',
        'Http/Middleware',
        'Http/Exceptions',
        'Http/Routes',
        'Console/Commands',
        'Views',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Kernel Structure
    |--------------------------------------------------------------------------
    | Directories scaffolded inside Domain/Shared/ and Application/Shared/.
    |
    */
    'shared_structure' => [
        'Domain/Shared/Contracts',
        'Domain/Shared/ValueObjects',
        'Domain/Shared/Exceptions',
        'Domain/Shared/Traits',
        'Domain/Shared/Events',
        'Application/Shared/DTOs',
        'Application/Shared/Contracts',
        'Application/Shared/Traits',
        'Application/Shared/Exceptions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Structure
    |--------------------------------------------------------------------------
    | Test directories scaffolded inside tests/ per context.
    |
    */
    'test_structure' => [
        'Unit',
        'Feature',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stub Overrides
    |--------------------------------------------------------------------------
    | Publish stubs to your application and customise them. Once published,
    | the package will prefer your app's stubs over its own built-in ones.
    |
    */
    'stub_path' => resource_path('stubs/ddd'),

    /*
    |--------------------------------------------------------------------------
    | File Generation
    |--------------------------------------------------------------------------
    | Control which placeholder files are generated inside each directory.
    |
    */
    'generate_gitkeep' => true,         // Add .gitkeep to every empty directory
    'generate_readme'  => true,         // Add a README.md stub per context

    /*
    |--------------------------------------------------------------------------
    | Route Registration
    |--------------------------------------------------------------------------
    | When a new context is scaffolded the ServiceProvider automatically
    | registers these route files (relative to its Presentation/Http/Routes/).
    |
    */
    'route_files' => [
        'api.php',
        'web.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Artisan Command Prefix
    |--------------------------------------------------------------------------
    | All package commands are grouped under this prefix.
    |
    */
    'command_prefix' => 'ddd',

];
