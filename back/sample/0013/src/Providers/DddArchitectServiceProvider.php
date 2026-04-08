<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Providers;

use Archify\DddArchitect\Commands\InfoCommand;
use Archify\DddArchitect\Commands\ListContextsCommand;
use Archify\DddArchitect\Commands\MakeAggregateCommand;
use Archify\DddArchitect\Commands\MakeCommandCommand;
use Archify\DddArchitect\Commands\MakeContextCommand;
use Archify\DddArchitect\Commands\MakeDtoCommand;
use Archify\DddArchitect\Commands\MakeEntityCommand;
use Archify\DddArchitect\Commands\MakeEventCommand;
use Archify\DddArchitect\Commands\MakeQueryCommand;
use Archify\DddArchitect\Commands\MakeRepositoryCommand;
use Archify\DddArchitect\Commands\MakeServiceCommand;
use Archify\DddArchitect\Commands\MakeSpecificationCommand;
use Archify\DddArchitect\Commands\MakeValueObjectCommand;
use Archify\DddArchitect\Commands\PublishStubsCommand;
use Archify\DddArchitect\Contracts\ContextRegistrar;
use Archify\DddArchitect\Generators\AggregateRootGenerator;
use Archify\DddArchitect\Generators\CommandGenerator;
use Archify\DddArchitect\Generators\CommandHandlerGenerator;
use Archify\DddArchitect\Generators\ContextScaffoldGenerator;
use Archify\DddArchitect\Generators\DomainEventGenerator;
use Archify\DddArchitect\Generators\DomainServiceGenerator;
use Archify\DddArchitect\Generators\DtoGenerator;
use Archify\DddArchitect\Generators\EloquentRepositoryGenerator;
use Archify\DddArchitect\Generators\EntityGenerator;
use Archify\DddArchitect\Generators\QueryGenerator;
use Archify\DddArchitect\Generators\QueryHandlerGenerator;
use Archify\DddArchitect\Generators\RepositoryInterfaceGenerator;
use Archify\DddArchitect\Generators\SharedKernelGenerator;
use Archify\DddArchitect\Generators\SpecificationGenerator;
use Archify\DddArchitect\Generators\ValueObjectGenerator;
use Archify\DddArchitect\Support\ContextResolver;
use Archify\DddArchitect\Support\FileGenerator;
use Archify\DddArchitect\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * DddArchitectServiceProvider — Main package service provider.
 *
 * Responsibilities
 * ────────────────
 * • Merges and publishes the package configuration file.
 * • Binds all core services into the Laravel service container.
 * • Registers every Artisan command.
 * • Auto-discovers and registers each bounded context's ServiceProvider
 *   (togglable via config('ddd-architect.auto_discover')).
 * • Publishes stubs to resource_path('stubs/ddd') via artisan vendor:publish.
 */
final class DddArchitectServiceProvider extends ServiceProvider
{
    // ──────────────────────────────────────────────────────────────────────────
    // register()
    // ──────────────────────────────────────────────────────────────────────────

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ddd-architect.php',
            'ddd-architect'
        );

        $this->bindCoreServices();
        $this->bindGenerators();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // boot()
    // ──────────────────────────────────────────────────────────────────────────

    public function boot(): void
    {
        $this->publishAssets();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        if (config('ddd-architect.auto_discover', true)) {
            $this->autoDiscoverContextProviders();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Service bindings
    // ──────────────────────────────────────────────────────────────────────────

    private function bindCoreServices(): void
    {
        // Filesystem
        $this->app->bindIf(Filesystem::class, fn () => new Filesystem());

        // StubRenderer — singleton so stub path scanning is done once
        $this->app->singleton(StubRenderer::class);

        // FileGenerator
        $this->app->singleton(FileGenerator::class, fn ($app) =>
            new FileGenerator($app->make(Filesystem::class))
        );

        // ContextResolver bound as both the concrete class and the interface
        $this->app->singleton(ContextResolver::class);
        $this->app->singleton(ContextRegistrar::class, fn ($app) =>
            $app->make(ContextResolver::class)
        );
    }

    private function bindGenerators(): void
    {
        // All generators are transient (new instance per resolve is fine)
        $generators = [
            EntityGenerator::class,
            ValueObjectGenerator::class,
            AggregateRootGenerator::class,
            DomainEventGenerator::class,
            DomainServiceGenerator::class,
            SpecificationGenerator::class,
            RepositoryInterfaceGenerator::class,
            EloquentRepositoryGenerator::class,
            CommandGenerator::class,
            CommandHandlerGenerator::class,
            QueryGenerator::class,
            QueryHandlerGenerator::class,
            DtoGenerator::class,
            ContextScaffoldGenerator::class,
        ];

        foreach ($generators as $class) {
            $this->app->bind($class, fn ($app) => new $class(
                $app->make(StubRenderer::class),
                $app->make(FileGenerator::class),
                $app->make(ContextResolver::class),
            ));
        }

        // SharedKernelGenerator has a different constructor
        $this->app->bind(SharedKernelGenerator::class, fn ($app) =>
            new SharedKernelGenerator(
                $app->make(StubRenderer::class),
                $app->make(FileGenerator::class),
            )
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Commands
    // ──────────────────────────────────────────────────────────────────────────

    private function registerCommands(): void
    {
        $this->commands([
            MakeContextCommand::class,
            MakeEntityCommand::class,
            MakeValueObjectCommand::class,
            MakeAggregateCommand::class,
            MakeEventCommand::class,
            MakeServiceCommand::class,
            MakeSpecificationCommand::class,
            MakeRepositoryCommand::class,
            MakeCommandCommand::class,
            MakeQueryCommand::class,
            MakeDtoCommand::class,
            ListContextsCommand::class,
            PublishStubsCommand::class,
            InfoCommand::class,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Auto-discovery
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Scan the configured base path for bounded context directories and
     * auto-register their ServiceProviders if found.
     *
     * Provider class pattern is driven by config('ddd-architect.provider_pattern').
     */
    private function autoDiscoverContextProviders(): void
    {
        $mode     = config('ddd-architect.mode', 'layered');
        $basePath = config("ddd-architect.paths.{$mode}");

        if (! File::isDirectory($basePath)) {
            return;
        }

        /** @var ContextResolver $resolver */
        $resolver = $this->app->make(ContextResolver::class);

        foreach (File::directories($basePath) as $dir) {
            $contextName  = basename($dir);
            $providerClass = $resolver->resolveProvider($contextName);

            // Register context in the registry
            $resolver->register($contextName, [
                'path'      => $dir,
                'namespace' => $resolver->resolveNamespace($contextName),
                'provider'  => $providerClass,
            ]);

            // Boot the context's ServiceProvider if it exists
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Publishing
    // ──────────────────────────────────────────────────────────────────────────

    private function publishAssets(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/../../config/ddd-architect.php' => config_path('ddd-architect.php'),
        ], 'ddd-architect-config');

        // Stubs
        $this->publishes([
            __DIR__ . '/../../stubs' => resource_path('stubs/ddd'),
        ], 'ddd-architect-stubs');

        // Both at once
        $this->publishes([
            __DIR__ . '/../../config/ddd-architect.php' => config_path('ddd-architect.php'),
            __DIR__ . '/../../stubs'                    => resource_path('stubs/ddd'),
        ], 'ddd-architect');
    }
}
