<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\AggregateGenerator;


class MakeAggregateCommand extends BaseCommand
{
    protected $signature = 'ddd:make-aggregate
        {context : Bounded context name}
        {name    : Aggregate root class name}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Aggregate Root inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new AggregateGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            aggregateName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Aggregate Root <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
