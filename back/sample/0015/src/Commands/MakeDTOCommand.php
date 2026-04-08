<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\DTOGenerator;


class MakeDTOCommand extends BaseCommand
{
    protected $signature = 'ddd:make-dto
        {context : Bounded context name}
        {name    : DTO name without "DTO" suffix (e.g. CreateOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Data Transfer Object inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DTOGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            dtoName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "DTO <options=bold>[{$name}DTO]</> created in context <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
