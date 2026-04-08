<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\PolicyGenerator;

class MakePolicyCommand extends BaseCommand
{
    protected $signature = 'ddd:make-policy
        {context : Bounded context name}
        {name    : Policy subject name (e.g. Order → creates OrderPolicy)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Policy inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new PolicyGenerator(
            config:      $this->config(),
            renderer:    $this->renderer(),
            files:       $this->fileGen(),
            contextName: $context,
            policyName:  $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success(
                "Policy <options=bold>[{$name}Policy]</> created in <options=bold>[{$context}]</>."
            );
        }

        return self::SUCCESS;
    }
}
