<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\DomainEventGenerator;


class MakeDomainEventCommand extends BaseCommand
{
    protected $signature = 'ddd:make-domain-event
        {context : Bounded context name}
        {name    : Domain event class name (e.g. OrderWasCreated)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Event inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DomainEventGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            eventName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Domain Event <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
