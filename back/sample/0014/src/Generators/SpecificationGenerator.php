<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class SpecificationGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $specName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $spec    = Str::studly($this->specName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/specification.stub' =>
                "{$base}/Domain/{$context}/Specifications/{$spec}.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->specName),
            layer: 'Domain\\' . Str::studly($this->contextName) . '\\Specifications',
            rootNs: $this->rootNamespace(),
        );
    }
}
