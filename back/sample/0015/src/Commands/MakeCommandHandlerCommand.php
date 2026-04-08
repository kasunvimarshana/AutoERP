<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\CommandHandlerGenerator;


class MakeCommandHandlerCommand extends BaseCommand
{
    protected $signature = 'ddd:make-command-handler
        {context : Bounded context name}
        {name    : Command name without "Command" suffix (e.g. CreateOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a CQRS Command + Handler pair inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new CommandHandlerGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            commandName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Command + Handler <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
