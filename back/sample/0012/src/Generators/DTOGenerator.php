<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class DTOGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $dtoName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $dto     = Str::studly($this->dtoName);
        $base    = base_path($this->config['base_path']);

        return [
            'application/dto.stub' =>
                "{$base}/Application/{$context}/DTOs/{$dto}DTO.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->dtoName),
            layer: 'Application\\' . Str::studly($this->contextName) . '\\DTOs',
            rootNs: $this->rootNamespace(),
        );
    }
}
