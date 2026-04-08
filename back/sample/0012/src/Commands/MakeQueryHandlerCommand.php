<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\QueryHandlerGenerator;


class MakeQueryHandlerCommand extends BaseCommand
{
    protected $signature = 'ddd:make-query-handler
        {context : Bounded context name}
        {name    : Query name without "Query" suffix (e.g. GetOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a CQRS Query + Handler pair inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new QueryHandlerGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $context,
            queryName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Query + Handler <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
