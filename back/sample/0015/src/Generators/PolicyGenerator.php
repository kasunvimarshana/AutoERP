<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class PolicyGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $policyName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $policy  = Str::studly($this->policyName);
        $base    = base_path($this->config['base_path']);

        return [
            'domain/policy.stub' =>
                "{$base}/Domain/{$context}/Policies/{$policy}Policy.php",
        ];
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->policyName),
            layer: 'Domain\\' . Str::studly($this->contextName) . '\\Policies',
            rootNs: $this->rootNamespace(),
        );
    }
}
