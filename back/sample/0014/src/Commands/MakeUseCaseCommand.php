<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\UseCaseGenerator;


class MakeUseCaseCommand extends BaseCommand
{
    protected $signature = 'ddd:make-use-case
        {context : Bounded context name}
        {name    : Use case name (e.g. CreateOrder, CancelOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create an Application Use Case inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new UseCaseGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            useCaseName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "UseCase <options=bold>[{$name}UseCase]</> created in context <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
