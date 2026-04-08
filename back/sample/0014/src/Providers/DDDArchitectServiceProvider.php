<?php

namespace YourVendor\LaravelDDDArchitect\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use YourVendor\LaravelDDDArchitect\Commands\DDDInfoCommand;
use YourVendor\LaravelDDDArchitect\Commands\ListContextsCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeAggregateCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeCommandHandlerCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeContextCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeDomainEventCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeDomainServiceCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeDTOCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeEntityCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeListenerCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeModelCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakePolicyCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeQueryHandlerCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeRepositoryCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeSpecificationCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeUseCaseCommand;
use YourVendor\LaravelDDDArchitect\Commands\MakeValueObjectCommand;
use YourVendor\LaravelDDDArchitect\Commands\PublishStubsCommand;
use YourVendor\LaravelDDDArchitect\Contracts\ContextRegistrar;
use YourVendor\LaravelDDDArchitect\Resolvers\ContextResolver;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;
use YourVendor\LaravelDDDArchitect\Support\StructureResolver;

class DDDArchitectServiceProvider extends ServiceProvider
{
    /**
     * All Artisan commands provided by this package.
     */
    protected array $commands = [
        MakeContextCommand::class,
        MakeEntityCommand::class,
        MakeValueObjectCommand::class,
        MakeUseCaseCommand::class,
        MakeRepositoryCommand::class,
        MakeDomainServiceCommand::class,
        MakeDomainEventCommand::class,
        MakeCommandHandlerCommand::class,
        MakeQueryHandlerCommand::class,
        MakeDTOCommand::class,
        MakeAggregateCommand::class,
        MakeSpecificationCommand::class,
        MakeModelCommand::class,
        MakeListenerCommand::class,
        MakePolicyCommand::class,
        ListContextsCommand::class,
        PublishStubsCommand::class,
        DDDInfoCommand::class,
    ];

    public function register(): void
    {
        // Merge package config (user config takes precedence)
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ddd-architect.php',
            'ddd-architect'
        );

        // Bind core contracts
        $this->app->singleton(ContextRegistrar::class, function ($app) {
            $raw      = $app['config']->get('ddd-architect');
            $resolved = StructureResolver::resolve($raw);
            return new ContextResolver($resolved);
        });

        $this->app->singleton(StubRenderer::class, function ($app) {
            return new StubRenderer(
                $app['config']->get('ddd-architect.stub_path'),
                __DIR__ . '/../../stubs'
            );
        });

        $this->app->singleton(FileGenerator::class, function () {
            return new FileGenerator();
        });

        // Register the facade accessor
        $this->app->alias(ContextRegistrar::class, 'ddd-architect');
    }

    public function boot(): void
    {
        $this->publishConfiguration();
        $this->publishStubs();

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        // Auto-discover and register bounded context ServiceProviders
        if (config('ddd-architect.auto_discover', true)) {
            $this->discoverAndRegisterContexts();
        }
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/ddd-architect.php' => config_path('ddd-architect.php'),
        ], 'ddd-architect-config');
    }

    /**
     * Publish stub templates for developer customisation.
     */
    protected function publishStubs(): void
    {
        $this->publishes([
            __DIR__ . '/../../stubs' => resource_path('stubs/ddd'),
        ], 'ddd-architect-stubs');
    }

    /**
     * Scan the configured base path and auto-register any bounded context
     * ServiceProvider found under Infrastructure/Providers/{Context}ServiceProvider.php
     */
    protected function discoverAndRegisterContexts(): void
    {
        $config    = config('ddd-architect');
        $basePath  = base_path($config['base_path']);
        $namespace = rtrim($config['namespace'], '\\');
        $pattern   = $config['provider_pattern'] ?? '{{Context}}ServiceProvider';

        // Look in Domain/, Application/ root siblings for context directories
        // By convention contexts live directly under base_path (e.g. app/Domain/Order/)
        $contextDirs = $this->resolveContextDirectories($basePath, $config);

        foreach ($contextDirs as $contextDir) {
            $contextName  = basename($contextDir);
            $providerClass = $this->buildProviderClass($namespace, $contextName, $pattern, $config);

            if ($providerClass && class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Resolve bounded context directories based on the architecture mode.
     */
    protected function resolveContextDirectories(string $basePath, array $config): array
    {
        $mode = $config['mode'] ?? 'full';
        $dirs = [];

        if (in_array($mode, ['full', 'minimal', 'custom'])) {
            // Contexts live in app/Domain/{Context}
            $domainBase = $basePath . DIRECTORY_SEPARATOR . 'Domain';
            if (File::isDirectory($domainBase)) {
                foreach (File::directories($domainBase) as $dir) {
                    if (basename($dir) !== 'Shared') {
                        $dirs[] = $dir;
                    }
                }
            }
        }

        return $dirs;
    }

    /**
     * Build the fully-qualified ServiceProvider class name for a context.
     */
    protected function buildProviderClass(
        string $namespace,
        string $contextName,
        string $pattern,
        array $config
    ): ?string {
        $providerName = str_replace('{{Context}}', $contextName, $pattern);

        // Supports both flat and Infrastructure/Providers/ nested structures
        $candidates = [
            "{$namespace}\\Infrastructure\\Providers\\{$providerName}",
            "{$namespace}\\Domain\\{$contextName}\\Infrastructure\\Providers\\{$providerName}",
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Return all services provided (deferred loading support).
     */
    public function provides(): array
    {
        return [
            ContextRegistrar::class,
            StubRenderer::class,
            FileGenerator::class,
            'ddd-architect',
        ];
    }
}
