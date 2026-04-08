<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\ModelGenerator;

class MakeModelCommand extends BaseCommand
{
    protected $signature = 'ddd:make-model
        {context   : Bounded context name}
        {name      : Eloquent model name (e.g. Order)}
        {--no-factory : Skip generating a Model Factory}
        {--no-seeder  : Skip generating a Seeder}
        {--force      : Overwrite if exists}';

    protected $description = 'Create an Eloquent Model, Factory, and Seeder for a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new ModelGenerator(
            config:       $this->config(),
            renderer:     $this->renderer(),
            files:        $this->fileGen(),
            contextName:  $context,
            modelName:    $name,
            withFactory:  ! $this->option('no-factory'),
            withSeeder:   ! $this->option('no-seeder'),
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Eloquent Model <options=bold>[{$name}Model]</> (+ Factory/Seeder) created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
