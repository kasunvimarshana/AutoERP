<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class ListenerGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $listenerName,
        protected string $eventClass = '',
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context  = Str::studly($this->contextName);
        $listener = Str::studly($this->listenerName);
        $base     = base_path($this->config['base_path']);

        return [
            'infrastructure/listener.stub' =>
                "{$base}/Infrastructure/Events/{$listener}Listener.php",
        ];
    }

    protected function tokens(): array
    {
        $tokens = StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->listenerName),
            layer: 'Infrastructure\\Events',
            rootNs: $this->rootNamespace(),
        );

        $tokens['eventClass'] = $this->eventClass
            ?: $this->rootNamespace() . '\\Domain\\' . Str::studly($this->contextName) . '\\Events\\' . Str::studly($this->listenerName) . 'Event';

        return $tokens;
    }
}
