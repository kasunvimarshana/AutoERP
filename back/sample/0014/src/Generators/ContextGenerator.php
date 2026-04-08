<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

/**
 * Generates the entire bounded-context directory/file structure.
 */
class ContextGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        FileGenerator $files,
        protected string $contextName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    // -------------------------------------------------------------------------
    // GeneratorContract
    // -------------------------------------------------------------------------

    public function generate(): array
    {
        $created = [];

        // 1. Create all directories with .gitkeep
        $this->scaffoldDirectories();

        // 2. Write stub files
        $created = array_merge($created, parent::generate());

        // 3. Optionally scaffold Shared kernel (once, not per context)
        if ($this->config['shared_kernel'] ?? true) {
            $created = array_merge($created, $this->scaffoldSharedKernel());
        }

        return $created;
    }

    // -------------------------------------------------------------------------
    // AbstractGenerator
    // -------------------------------------------------------------------------

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $base    = base_path($this->config['base_path']);
        $stubs   = [];

        $mode   = $this->config['mode'] ?? 'full';
        $layers = $this->resolveActiveLayers($mode);

        if (in_array('domain', $layers)) {
            $stubs['providers/context-service-provider.stub'] =
                "{$base}/Domain/{$context}/Providers/{$context}ServiceProvider.php";

            $stubs['domain/entity.stub'] =
                "{$base}/Domain/{$context}/Entities/{$context}.php";

            $stubs['domain/repository-interface.stub'] =
                "{$base}/Domain/{$context}/Repositories/{$context}RepositoryInterface.php";

            $stubs['domain/domain-event.stub'] =
                "{$base}/Domain/{$context}/Events/{$context}Created.php";
        }

        if (in_array('application', $layers)) {
            $stubs['application/use-case.stub'] =
                "{$base}/Application/{$context}/UseCases/Create{$context}UseCase.php";

            $stubs['application/dto.stub'] =
                "{$base}/Application/{$context}/DTOs/Create{$context}DTO.php";

            $stubs['application/command.stub'] =
                "{$base}/Application/{$context}/Commands/Create{$context}Command.php";

            $stubs['application/query.stub'] =
                "{$base}/Application/{$context}/Queries/Get{$context}Query.php";

            $stubs['application/command-handler.stub'] =
                "{$base}/Application/{$context}/Handlers/Create{$context}Handler.php";
        }

        if (in_array('infrastructure', $layers)) {
            $stubs['infrastructure/eloquent-model.stub'] =
                "{$base}/Infrastructure/Persistence/Eloquent/{$context}Model.php";

            $stubs['infrastructure/eloquent-repository.stub'] =
                "{$base}/Infrastructure/Persistence/Repositories/Eloquent{$context}Repository.php";

            $stubs['infrastructure/service-provider.stub'] =
                "{$base}/Infrastructure/Providers/{$context}InfrastructureServiceProvider.php";

            $stubs['infrastructure/migration.stub'] =
                "{$base}/Infrastructure/Persistence/Migrations/" .
                now()->format('Y_m_d_His') . "_create_" . Str::snake(Str::plural($context)) . "_table.php";
        }

        if (in_array('presentation', $layers)) {
            $stubs['presentation/api-controller.stub'] =
                "{$base}/Presentation/Http/Controllers/Api/{$context}Controller.php";

            $stubs['presentation/form-request.stub'] =
                "{$base}/Presentation/Http/Requests/Store{$context}Request.php";

            $stubs['presentation/api-resource.stub'] =
                "{$base}/Presentation/Http/Resources/{$context}Resource.php";

            $stubs['presentation/api-routes.stub'] =
                "{$base}/Presentation/Http/Routes/api.php";

            $stubs['presentation/web-routes.stub'] =
                "{$base}/Presentation/Http/Routes/web.php";
        }

        // Tests
        $stubs['tests/unit-test.stub'] =
            base_path("tests/Unit/{$context}/{$context}Test.php");

        $stubs['tests/feature-test.stub'] =
            base_path("tests/Feature/{$context}/{$context}ApiTest.php");

        return $stubs;
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        return StubRenderer::buildTokens(
            context: $context,
            className: $context,
            layer: "Domain\\{$context}",
            rootNs: $this->rootNamespace(),
        );
    }

    // -------------------------------------------------------------------------
    // Directory Scaffolding
    // -------------------------------------------------------------------------

    protected function scaffoldDirectories(): void
    {
        $base    = base_path($this->config['base_path']);
        $context = Str::studly($this->contextName);
        $mode    = $this->config['mode'] ?? 'full';
        $layers  = $this->resolveActiveLayers($mode);
        $gitkeep = $this->config['generate_gitkeep'] ?? true;

        $paths = [];

        if (in_array('domain', $layers)) {
            foreach ($this->config['domain_structure'] ?? [] as $dir) {
                $paths[] = "{$base}/Domain/{$context}/{$dir}";
            }
        }

        if (in_array('application', $layers)) {
            foreach ($this->config['application_structure'] ?? [] as $dir) {
                $paths[] = "{$base}/Application/{$context}/{$dir}";
            }
        }

        if (in_array('infrastructure', $layers)) {
            foreach ($this->config['infrastructure_structure'] ?? [] as $dir) {
                $paths[] = "{$base}/Infrastructure/{$dir}";
            }
        }

        if (in_array('presentation', $layers)) {
            foreach ($this->config['presentation_structure'] ?? [] as $dir) {
                $paths[] = "{$base}/Presentation/{$dir}";
            }
        }

        // Test directories
        foreach (['Unit', 'Feature'] as $type) {
            $paths[] = base_path("tests/{$type}/{$context}");
        }

        $this->files->ensureDirectories($paths, $gitkeep);
    }

    protected function scaffoldSharedKernel(): array
    {
        $base    = base_path($this->config['base_path']);
        $gitkeep = $this->config['generate_gitkeep'] ?? true;
        $paths   = [];

        foreach ($this->config['shared_structure'] ?? [] as $dir) {
            $paths[] = "{$base}/{$dir}";
        }

        $this->files->ensureDirectories($paths, $gitkeep);

        // Write Shared kernel base stubs (only if they don't exist yet)
        $created = [];
        $sharedStubs = [
            'shared/aggregate-root.stub'      => "{$base}/Domain/Shared/Contracts/AggregateRoot.php",
            'shared/entity-contract.stub'     => "{$base}/Domain/Shared/Contracts/EntityContract.php",
            'shared/value-object.stub'        => "{$base}/Domain/Shared/ValueObjects/AbstractValueObject.php",
            'shared/domain-event.stub'        => "{$base}/Domain/Shared/Events/DomainEvent.php",
            'shared/domain-exception.stub'    => "{$base}/Domain/Shared/Exceptions/DomainException.php",
            'shared/repository-contract.stub' => "{$base}/Domain/Shared/Contracts/RepositoryContract.php",
            'shared/uuid-value-object.stub'   => "{$base}/Domain/Shared/ValueObjects/Uuid.php",
            'shared/email-value-object.stub'  => "{$base}/Domain/Shared/ValueObjects/Email.php",
            'shared/money-value-object.stub'  => "{$base}/Domain/Shared/ValueObjects/Money.php",
        ];

        $tokens = StubRenderer::buildTokens('Shared', 'Shared', 'Domain\Shared', $this->rootNamespace());

        foreach ($sharedStubs as $stub => $target) {
            $content = $this->renderer->render($stub, $tokens);
            if ($this->files->write($target, $content, false)) {
                $created[] = $target;
            }
        }

        return $created;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function resolveActiveLayers(string $mode): array
    {
        return match ($mode) {
            'domain'  => ['domain'],
            'minimal' => ['domain', 'application'],
            'custom'  => $this->config['layers'] ?? ['domain', 'application', 'infrastructure', 'presentation'],
            default   => ['domain', 'application', 'infrastructure', 'presentation'],
        };
    }
}
