<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class QueryHandlerGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $queryName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $name    = Str::studly($this->queryName);
        $base    = base_path($this->config['base_path']);

        return [
            'application/query.stub' =>
                "{$base}/Application/{$context}/Queries/{$name}Query.php",
            'application/query-handler.stub' =>
                "{$base}/Application/{$context}/Handlers/{$name}QueryHandler.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->queryName),
            layer: 'Application\\' . Str::studly($this->contextName) . '\\Queries',
            rootNs: $this->rootNamespace(),
        );
    }
}
