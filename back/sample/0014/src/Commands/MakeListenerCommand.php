<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\ListenerGenerator;

class MakeListenerCommand extends BaseCommand
{
    protected $signature = 'ddd:make-listener
        {context : Bounded context name}
        {name    : Listener class name (e.g. SendOrderConfirmation)}
        {--event= : Fully-qualified domain event class this listener handles}
        {--force  : Overwrite if exists}';

    protected $description = 'Create an Infrastructure Event Listener for a domain event';

    public function handle(): int
    {
        $context    = Str::studly($this->argument('context'));
        $name       = Str::studly($this->argument('name'));
        $eventClass = $this->option('event') ?? '';

        $generator = new ListenerGenerator(
            config:       $this->config(),
            renderer:     $this->renderer(),
            files:        $this->fileGen(),
            contextName:  $context,
            listenerName: $name,
            eventClass:   $eventClass,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Listener <options=bold>[{$name}Listener]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
