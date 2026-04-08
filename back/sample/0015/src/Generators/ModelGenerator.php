<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class ModelGenerator extends AbstractGenerator
{
    public function __construct(
        array $config,
        StubRenderer $renderer,
        $files,
        protected string $contextName,
        protected string $modelName,
        protected bool $withFactory = true,
        protected bool $withSeeder  = true,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        $context = Str::studly($this->contextName);
        $model   = Str::studly($this->modelName);
        $base    = base_path($this->config['base_path']);

        $stubs = [
            'infrastructure/eloquent-model.stub' =>
                "{$base}/Infrastructure/Persistence/Eloquent/{$model}Model.php",
        ];

        if ($this->withFactory) {
            $stubs['infrastructure/factory.stub'] =
                "{$base}/Infrastructure/Persistence/Factories/{$model}Factory.php";
        }

        if ($this->withSeeder) {
            $stubs['infrastructure/seeder.stub'] =
                "{$base}/Infrastructure/Persistence/Seeders/{$model}Seeder.php";
        }

        return $stubs;
    }

    protected function tokens(): array
    {
        return StubRenderer::buildTokens(
            context: Str::studly($this->contextName),
            className: Str::studly($this->modelName),
            layer: 'Infrastructure\\Persistence\\Eloquent',
            rootNs: $this->rootNamespace(),
        );
    }
}
