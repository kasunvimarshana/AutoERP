<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class EntityGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        FileGenerator $files,
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
            'domain/entity.stub' =>
                "{$base}/Domain/{$context}/Entities/{$entity}.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $entity  = Str::studly($this->entityName);

        return StubRenderer::buildTokens(
            context: $context,
            className: $entity,
            layer: "Domain\\{$context}\\Entities",
            rootNs: $this->rootNamespace(),
        );
    }
}
