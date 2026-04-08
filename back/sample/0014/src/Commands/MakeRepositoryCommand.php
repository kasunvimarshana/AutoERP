<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\RepositoryGenerator;


class MakeRepositoryCommand extends BaseCommand
{
    protected $signature = 'ddd:make-repository
        {context : Bounded context name}
        {name    : Entity / Aggregate name the repository manages}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Repository interface + Eloquent implementation';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new RepositoryGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            entityName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Repository interface + Eloquent implementation created for <options=bold>[{$name}]</>."
            );
        }

        return self::SUCCESS;
    }
}
