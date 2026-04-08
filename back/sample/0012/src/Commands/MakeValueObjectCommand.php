<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\ValueObjectGenerator;


class MakeValueObjectCommand extends BaseCommand
{
    protected $signature = 'ddd:make-value-object
        {context : The bounded context name}
        {name    : The ValueObject class name (e.g. OrderStatus, Price)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a DDD immutable Value Object inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new ValueObjectGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            valueObjectName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("ValueObject <options=bold>[{$name}]</> created in context <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}
