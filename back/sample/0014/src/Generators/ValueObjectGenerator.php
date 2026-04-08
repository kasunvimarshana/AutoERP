<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class ValueObjectGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        FileGenerator $files,
        protected string $contextName,
        protected string $valueObjectName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $vo      = Str::studly($this->valueObjectName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/value-object.stub' =>
                "{$base}/Domain/{$context}/ValueObjects/{$vo}.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $vo      = Str::studly($this->valueObjectName);

        return StubRenderer::buildTokens(
            context: $context,
            className: $vo,
            layer: "Domain\\{$context}\\ValueObjects",
            rootNs: $this->rootNamespace(),
        );
    }
}
