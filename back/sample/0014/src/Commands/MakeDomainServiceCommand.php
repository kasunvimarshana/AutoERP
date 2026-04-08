<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\DomainServiceGenerator;


class MakeDomainServiceCommand extends BaseCommand
{
    protected $signature = 'ddd:make-domain-service
        {context : Bounded context name}
        {name    : Domain service class name}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Service inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DomainServiceGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            serviceName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Domain Service <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
