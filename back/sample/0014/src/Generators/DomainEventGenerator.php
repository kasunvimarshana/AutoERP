<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class DomainEventGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $eventName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $event   = Str::studly($this->eventName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/domain-event.stub' =>
                "{$base}/Domain/{$context}/Events/{$event}.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->eventName),
            layer: 'Domain\\' . Str::studly($this->contextName) . '\\Events',
            rootNs: $this->rootNamespace(),
        );
    }
}
