<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

class DDDInfoCommand extends BaseCommand
{
    protected $signature = 'ddd:info';
    protected $description = 'Display current DDD Architect configuration and available commands';

    public function handle(): int
    {
        $config = $this->config();

        $this->newLine();
        $this->line('  <options=bold>Laravel DDD Architect — Configuration</>');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Mode',              $config['mode']           ?? 'full'],
            ['Base Path',         $config['base_path']      ?? 'app'],
            ['Root Namespace',    $config['namespace']      ?? 'App'],
            ['Auto Discover',     ($config['auto_discover']   ?? true) ? '<fg=green>Yes</>' : '<fg=yellow>No</>'],
            ['Shared Kernel',     ($config['shared_kernel']   ?? true) ? '<fg=green>Yes</>' : '<fg=yellow>No</>'],
            ['Generate .gitkeep', ($config['generate_gitkeep'] ?? true) ? '<fg=green>Yes</>' : '<fg=yellow>No</>'],
            ['Stub Override Path', $config['stub_path']     ?? resource_path('stubs/ddd')],
        ]);

        $this->newLine();
        $this->line('  <options=bold>Available Commands</>');
        $this->newLine();

        $this->table(['Command', 'Description'], [
            ['<fg=cyan>ddd:make-context {name}</>',            'Scaffold a full bounded context (all layers)'],
            ['<fg=cyan>ddd:make-entity {ctx} {name}</>',       'Create a Domain Entity'],
            ['<fg=cyan>ddd:make-value-object {ctx} {name}</>', 'Create an immutable Value Object'],
            ['<fg=cyan>ddd:make-aggregate {ctx} {name}</>',    'Create an Aggregate Root'],
            ['<fg=cyan>ddd:make-use-case {ctx} {name}</>',     'Create an Application Use Case'],
            ['<fg=cyan>ddd:make-repository {ctx} {name}</>',   'Create Repository interface + Eloquent implementation'],
            ['<fg=cyan>ddd:make-domain-service {ctx} {n}</>',  'Create a Domain Service'],
            ['<fg=cyan>ddd:make-domain-event {ctx} {name}</>', 'Create a Domain Event'],
            ['<fg=cyan>ddd:make-command-handler {ctx} {n}</>', 'Create a CQRS Command + Handler pair'],
            ['<fg=cyan>ddd:make-query-handler {ctx} {n}</>',   'Create a CQRS Query + Handler pair'],
            ['<fg=cyan>ddd:make-dto {ctx} {name}</>',          'Create a Data Transfer Object'],
            ['<fg=cyan>ddd:make-specification {ctx} {n}</>',   'Create a composable Specification'],
            ['<fg=cyan>ddd:list</>',                            'List all discovered bounded contexts'],
            ['<fg=cyan>ddd:publish-stubs</>',                   'Publish stubs to resources/stubs/ddd/'],
            ['<fg=cyan>ddd:info</>',                            'Show this information screen'],
        ]);

        $this->newLine();
        $this->line('  All <fg=cyan>make:*</> commands accept <options=bold>--force</> to overwrite existing files.');
        $this->line('  Run <options=bold>php artisan ddd:make-context --help</> for per-command options.');
        $this->newLine();

        return self::SUCCESS;
    }
}
