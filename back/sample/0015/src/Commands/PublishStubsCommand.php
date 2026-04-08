<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

class PublishStubsCommand extends BaseCommand
{
    protected $signature = 'ddd:publish-stubs
        {--force : Overwrite already-published stubs}';

    protected $description = 'Publish DDD stub templates to resources/stubs/ddd/ for customisation';

    public function handle(): int
    {
        $this->callSilent('vendor:publish', [
            '--tag'   => 'ddd-architect-stubs',
            '--force' => $this->option('force'),
        ]);

        $dest = resource_path('stubs/ddd');

        $this->components->success(
            "DDD stubs published to <options=bold>{$dest}</>."
        );
        $this->newLine();
        $this->line('  Edit any <fg=cyan>.stub</> file — the package automatically prefers your versions.');
        $this->line('  Re-run with <options=bold>--force</> to reset to package defaults.');
        $this->newLine();

        return self::SUCCESS;
    }
}
