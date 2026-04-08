<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class DomainServiceGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $serviceName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $service = Str::studly($this->serviceName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/domain-service.stub' =>
                "{$base}/Domain/{$context}/Services/{$service}.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->serviceName),
            layer: 'Domain\\' . Str::studly($this->contextName) . '\\Services',
            rootNs: $this->rootNamespace(),
        );
    }
}
