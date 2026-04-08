<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class CommandHandlerGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $commandName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->commandName);
        $base    = base_path($this->config['base_path']);

        return [
            'application/command.stub' =>
                "{$base}/Application/{$context}/Commands/{$name}Command.php",
            'application/command-handler.stub' =>
                "{$base}/Application/{$context}/Handlers/{$name}Handler.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->commandName),
            layer: 'Application\\' . Str::studly($this->contextName) . '\\Commands',
            rootNs: $this->rootNamespace(),
        );
    }
}
