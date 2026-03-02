<?php

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */
    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    */
    'stubs' => [
        'enabled' => false,
        'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
        'files' => [
            'routes/web' => 'routes/web.php',
            'routes/api' => 'routes/api.php',
            'views/index' => 'resources/views/index.blade.php',
            'views/master' => 'resources/views/components/layouts/master.blade.php',
            'scaffold/config' => 'config/config.php',
            'composer' => 'composer.json',
            'assets/js/app' => 'resources/assets/js/app.js',
            'assets/sass/app' => 'resources/assets/sass/app.scss',
            'vite' => 'vite.config.js',
            'package' => 'package.json',
        ],
        'replacements' => [
            /**
             * Define custom replacements for each section.
             * You can specify a closure for dynamic values.
             *
             * Example:
             *
             * 'composer' => [
             *      'CUSTOM_KEY' => fn (\Nwidart\Modules\Generators\ModuleGenerator $generator) => $generator->getModule()->getLowerName() . '-module',
             *      'CUSTOM_KEY2' => fn () => 'custom text',
             *      'LOWER_NAME',
             *      'STUDLY_NAME',
             *      // ...
             * ],
             *
             * Note: Keys should be in UPPERCASE.
             */
            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'routes/api' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'vite' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME'],
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
                'APP_FOLDER_NAME',
            ],
        ],
        'gitkeep' => true,
    ],
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path is used to save the generated module.
        | This path will also be added automatically to the list of scanned folders.
        |
        */
        'modules' => base_path('../Modules'),

        /*
        |--------------------------------------------------------------------------
        | Modules assets path
        |--------------------------------------------------------------------------
        |
        | Here you may update the modules' assets path.
        |
        */
        'assets' => public_path('modules'),

        /*
        |--------------------------------------------------------------------------
        | The migrations' path
        |--------------------------------------------------------------------------
        |
        | Where you run the 'module:publish-migration' command, where do you publish the
        | the migration files?
        |
        */
        'migration' => base_path('database/migrations'),

        /*
        |--------------------------------------------------------------------------
        | The app path
        |--------------------------------------------------------------------------
        |
        | app folder name
        | for example can change it to 'src' or 'App'
        */
        'app_folder' => 'app/',

        /*
        |--------------------------------------------------------------------------
        | Generator path
        |--------------------------------------------------------------------------
        | Customise the paths where the folders will be generated.
        | Setting the generate key to false will not generate that folder
        */
        'generator' => [
            // Application layer
            'command' => ['path' => 'Application/Commands', 'generate' => false],
            'jobs' => ['path' => 'Application/Jobs', 'generate' => false],
            'services' => ['path' => 'Application/Services', 'generate' => false],
            'actions' => ['path' => 'Application/Handlers', 'generate' => false],

            // Domain layer
            'model' => ['path' => 'Domain/Entities', 'generate' => true],
            'event' => ['path' => 'Domain/Events', 'generate' => false],
            'repository' => ['path' => 'Domain/Contracts', 'generate' => false],
            'enums' => ['path' => 'Domain/Enums', 'generate' => false],
            'exceptions' => ['path' => 'Domain/Exceptions', 'generate' => false],

            // Infrastructure layer
            'provider' => ['path' => 'Infrastructure/Providers', 'generate' => true],
            'route-provider' => ['path' => 'Infrastructure/Providers', 'generate' => false],
            'migration' => ['path' => 'Infrastructure/Database/Migrations', 'generate' => true],
            'seeder' => ['path' => 'Infrastructure/Database/Seeders', 'generate' => true],
            'factory' => ['path' => 'Infrastructure/Database/Factories', 'generate' => true],
            'observer' => ['path' => 'Infrastructure/Observers', 'generate' => false],
            'listener' => ['path' => 'Infrastructure/Listeners', 'generate' => false],

            // Interfaces layer
            'controller' => ['path' => 'Interfaces/Http/Controllers', 'generate' => true],
            'request' => ['path' => 'Interfaces/Http/Requests', 'generate' => false],
            'resource' => ['path' => 'Interfaces/Http/Resources', 'generate' => false],
            'policies' => ['path' => 'Interfaces/Http/Policies', 'generate' => false],
            'filter' => ['path' => 'Interfaces/Http/Middleware', 'generate' => false],

            // Other
            'config' => ['path' => 'config', 'generate' => true],
            'lang' => ['path' => 'lang', 'generate' => false],
            'assets' => ['path' => 'resources/assets', 'generate' => false],
            'views' => ['path' => 'resources/views', 'generate' => false],
            'routes' => ['path' => 'routes', 'generate' => true],
            'test-feature' => ['path' => 'Tests/Feature', 'generate' => true],
            'test-unit' => ['path' => 'Tests/Unit', 'generate' => true],

            // Disabled defaults
            'casts' => ['path' => 'Domain/Casts', 'generate' => false],
            'channels' => ['path' => 'Infrastructure/Broadcasting', 'generate' => false],
            'class' => ['path' => 'Application/Classes', 'generate' => false],
            'component-class' => ['path' => 'Interfaces/Components', 'generate' => false],
            'emails' => ['path' => 'Infrastructure/Mail', 'generate' => false],
            'helpers' => ['path' => 'Application/Helpers', 'generate' => false],
            'interfaces' => ['path' => 'Domain/Contracts', 'generate' => false],
            'notifications' => ['path' => 'Infrastructure/Notifications', 'generate' => false],
            'rules' => ['path' => 'Application/Rules', 'generate' => false],
            'scopes' => ['path' => 'Infrastructure/Scopes', 'generate' => false],
            'traits' => ['path' => 'Domain/Traits', 'generate' => false],
            'component-view' => ['path' => 'resources/views/components', 'generate' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Discover of Modules
    |--------------------------------------------------------------------------
    |
    | Here you configure auto discover of module
    | This is useful for simplify module providers.
    |
    */
    'auto-discover' => [
        /*
        |--------------------------------------------------------------------------
        | Migrations
        |--------------------------------------------------------------------------
        |
        | This option for register migration automatically.
        |
        */
        'migrations' => true,

        /*
        |--------------------------------------------------------------------------
        | Translations
        |--------------------------------------------------------------------------
        |
        | This option for register lang file automatically.
        |
        */
        'translations' => false,

    ],

    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. You can add your own commands to merge section.
    |
    */
    'commands' => ConsoleServiceProvider::defaultCommands()
        ->merge([
            // New commands go here
        ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */
    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for the composer.json file, generated by this package
    |
    */
    'composer' => [
        'vendor' => env('MODULE_VENDOR', 'nwidart'),
        'author' => [
            'name' => env('MODULE_AUTHOR_NAME', 'Nicolas Widart'),
            'email' => env('MODULE_AUTHOR_EMAIL', 'n.widart@gmail.com'),
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Choose what laravel-modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |--------------------------------------------------------------------------
    */
    'register' => [
        'translations' => true,
        /**
         * load files on boot or register method
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database, etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */
    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
        ],
    ],

    'activator' => 'file',
];
