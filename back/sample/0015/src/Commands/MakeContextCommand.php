<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Generators\ContextGenerator;


class MakeContextCommand extends BaseCommand
{
    protected $signature = 'ddd:make-context
        {name : The bounded context name (e.g. Order, User, Billing)}
        {--mode= : Override architecture mode (full|domain|minimal|custom)}
        {--force : Overwrite existing files}
        {--no-shared : Skip Shared kernel scaffolding}
        {--no-tests : Skip test directory scaffolding}';

    protected $description = 'Scaffold a complete DDD bounded context (Domain + Application + Infrastructure + Presentation)';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        $this->components->info("Scaffolding bounded context: <options=bold>{$name}</>");

        $config = $this->buildConfig();

        $generator = new ContextGenerator(
            config: $config,
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();

        if (empty($created)) {
            $this->components->warn("No new files were created. Use --force to overwrite.");
            return self::SUCCESS;
        }

        $this->reportCreated($created);

        $this->newLine();
        $this->components->success("Bounded context <options=bold>[{$name}]</> scaffolded successfully.");
        $this->newLine();
        $this->line("  <fg=gray>Next steps:</>");
        $this->line("  1. Add your business logic to <fg=cyan>app/Domain/{$name}/Entities/{$name}.php</>");
        $this->line("  2. Bind the repository in <fg=cyan>app/Infrastructure/Providers/{$name}InfrastructureServiceProvider.php</>");
        $this->line("  3. Register the provider in <fg=cyan>bootstrap/providers.php</> (Laravel 11+) or <fg=cyan>config/app.php</>");
        $this->newLine();

        return self::SUCCESS;
    }

    protected function buildConfig(): array
    {
        $config = $this->config();

        if ($mode = $this->option('mode')) {
            $config['mode'] = $mode;
        }

        if ($this->option('no-shared')) {
            $config['shared_kernel'] = false;
        }

        if ($this->option('no-tests')) {
            $config['test_structure'] = [];
        }

        return $config;
    }
}
