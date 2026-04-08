<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class RepositoryGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        $renderer,
        $files,
        protected string $contextName,
        protected string $entityName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $entity  = Str::studly($this->entityName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/repository-interface.stub' =>
                "{$base}/Domain/{$context}/Repositories/{$entity}RepositoryInterface.php",

            'infrastructure/eloquent-repository.stub' =>
                "{$base}/Infrastructure/Persistence/Repositories/Eloquent{$entity}Repository.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $entity  = Str::studly($this->entityName);

        return StubRenderer::buildTokens(
            context: $context,
            className: $entity,
            layer: "Domain\\{$context}\\Repositories",
            rootNs: $this->rootNamespace(),
        );
    }
}
