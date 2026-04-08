<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\SpecificationGenerator;


class MakeSpecificationCommand extends BaseCommand
{
    protected $signature = 'ddd:make-specification
        {context : Bounded context name}
        {name    : Specification class name (e.g. OrderIsActive)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Specification (business rule object) inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new SpecificationGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            specName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Specification <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
