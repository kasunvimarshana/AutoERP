<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

// ---------------------------------------------------------------------------

class DomainEventGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $eventName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $event   = Str::studly($this->eventName);
        $base    = base_path($this->config['base_path']);

        return ['domain/domain-event.stub' => "{$base}/Domain/{$context}/Events/{$event}.php"];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $event   = Str::studly($this->eventName);
        return StubRenderer::buildTokens($context, $event, "Domain\\{$context}\\Events", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class CommandHandlerGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $commandName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->commandName);
        $base    = base_path($this->config['base_path']);

        return [
            'application/command.stub'         => "{$base}/Application/{$context}/Commands/{$name}Command.php",
            'application/command-handler.stub' => "{$base}/Application/{$context}/Handlers/{$name}Handler.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->commandName);
        return StubRenderer::buildTokens($context, $name, "Application\\{$context}\\Commands", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class QueryHandlerGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $queryName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->queryName);
        $base    = base_path($this->config['base_path']);

        return [
            'application/query.stub'         => "{$base}/Application/{$context}/Queries/{$name}Query.php",
            'application/query-handler.stub' => "{$base}/Application/{$context}/Handlers/{$name}QueryHandler.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->queryName);
        return StubRenderer::buildTokens($context, $name, "Application\\{$context}\\Queries", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class DTOGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $dtoName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $dto     = Str::studly($this->dtoName);
        $base    = base_path($this->config['base_path']);

        return ['application/dto.stub' => "{$base}/Application/{$context}/DTOs/{$dto}DTO.php"];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $dto     = Str::studly($this->dtoName);
        return StubRenderer::buildTokens($context, $dto, "Application\\{$context}\\DTOs", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class AggregateGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $aggregateName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context   = Str::studly($this->contextName);
        $aggregate = Str::studly($this->aggregateName);
        $base      = base_path($this->config['base_path']);

        return ['domain/aggregate.stub' => "{$base}/Domain/{$context}/Aggregates/{$aggregate}.php"];
    }

    protected function tokens(): array
    {
        $context   = Str::studly($this->contextName);
        $aggregate = Str::studly($this->aggregateName);
        return StubRenderer::buildTokens($context, $aggregate, "Domain\\{$context}\\Aggregates", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class SpecificationGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $specName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $spec    = Str::studly($this->specName);
        $base    = base_path($this->config['base_path']);

        return ['domain/specification.stub' => "{$base}/Domain/{$context}/Specifications/{$spec}.php"];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $spec    = Str::studly($this->specName);
        return StubRenderer::buildTokens($context, $spec, "Domain\\{$context}\\Specifications", $this->rootNamespace());
    }
}

// ---------------------------------------------------------------------------

class DomainServiceGenerator extends AbstractGenerator
{
    public function __construct(array $config, $renderer, $files,
        protected string $contextName, protected string $serviceName) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $service = Str::studly($this->serviceName);
        $base    = base_path($this->config['base_path']);

        return ['domain/domain-service.stub' => "{$base}/Domain/{$context}/Services/{$service}.php"];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $service = Str::studly($this->serviceName);
        return StubRenderer::buildTokens($context, $service, "Domain\\{$context}\\Services", $this->rootNamespace());
    }
}
