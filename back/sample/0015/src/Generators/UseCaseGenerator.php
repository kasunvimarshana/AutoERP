<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;

class UseCaseGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        $renderer,
        $files,
        protected string $contextName,
        protected string $useCaseName,
        protected string $action = 'Create',
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context  = Str::studly($this->contextName);
        $useCase  = Str::studly($this->useCaseName);
        $base     = base_path($this->config['base_path']);

        return [
            'application/use-case.stub' =>
                "{$base}/Application/{$context}/UseCases/{$useCase}UseCase.php",
        ];
    }

    protected function tokens(): array
    {
        $context = Str::studly($this->contextName);
        $useCase = Str::studly($this->useCaseName);

        return array_merge(
            \YourVendor\LaravelDDDArchitect\Support\StubRenderer::buildTokens(
                context: $context,
                className: $useCase,
                layer: "Application\\{$context}\\UseCases",
                rootNs: $this->rootNamespace(),
            ),
            ['action' => Str::studly($this->action)]
        );
    }
}
