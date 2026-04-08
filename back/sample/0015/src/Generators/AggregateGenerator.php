<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class AggregateGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $aggregateName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context   = Str::studly($this->contextName);
        $aggregate = Str::studly($this->aggregateName);
        $base      = base_path($this->config['base_path']);

        return [
            'domain/aggregate.stub' =>
                "{$base}/Domain/{$context}/Aggregates/{$aggregate}.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->aggregateName),
            layer: 'Domain\\' . Str::studly($this->contextName) . '\\Aggregates',
            rootNs: $this->rootNamespace(),
        );
    }
}
