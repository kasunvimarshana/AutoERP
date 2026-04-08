<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\EntityGenerator;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;

class MakeEntityCommand extends BaseCommand
{
    protected $signature = 'ddd:make-entity
        {context : The bounded context name (e.g. Order)}
        {name    : The entity class name (e.g. Order, LineItem)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a DDD Domain Entity inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $this->ensureContextExists($context);

        $generator = new EntityGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            entityName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Entity <options=bold>[{$name}]</> created in context <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }

    protected function ensureContextExists(string $context): void
    {
        if (! $this->registrar()->exists($context)) {
            if ($this->confirm("Bounded context [{$context}] does not exist. Scaffold it now?", true)) {
                $this->call('ddd:make-context', ['name' => $context]);
            }
        }
    }
}
