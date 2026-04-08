<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Contracts\ContextRegistrar;
use Archify\DddArchitect\Support\StubRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * InfoCommand — Display package configuration and status.
 *
 * Usage:
 *   php artisan ddd:info
 */
final class InfoCommand extends Command
{
    protected $signature   = 'ddd:info';
    protected $description = 'Display DDD Architect configuration, status, and available commands';

    public function __construct(
        private readonly ContextRegistrar $registrar,
        private readonly StubRenderer     $renderer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $mode        = config('ddd-architect.mode', 'layered');
        $basePath    = config("ddd-architect.paths.{$mode}");
        $nsRoot      = config("ddd-architect.namespaces.{$mode}");
        $autoDiscover = config('ddd-architect.auto_discover', true) ? '<fg=green>enabled</>' : '<fg=red>disabled</>';
        $contexts    = count($this->registrar->all());

        $this->newLine();
        $this->line('  <fg=blue;options=bold>╔══════════════════════════════════════╗</>');
        $this->line('  <fg=blue;options=bold>║      DDD Architect for Laravel       ║</>');
        $this->line('  <fg=blue;options=bold>╚══════════════════════════════════════╝</>');
        $this->newLine();

        // ── Configuration ──────────────────────────────────────────────────
        $this->line('  <fg=white;options=bold>Configuration</>');
        $this->newLine();
        $this->line("  <fg=gray>Architecture mode</>  <fg=cyan>{$mode}</>");
        $this->line("  <fg=gray>Base path</>          <fg=white>{$basePath}</>");
        $this->line("  <fg=gray>Root namespace</>     <fg=white>{$nsRoot}</>");
        $this->line("  <fg=gray>Auto-discovery</>     {$autoDiscover}");
        $this->line("  <fg=gray>Registered contexts</> <fg=cyan>{$contexts}</>");

        $this->newLine();

        // ── Layer names ────────────────────────────────────────────────────
        $this->line('  <fg=white;options=bold>Layer Names</>');
        $this->newLine();
        foreach (config('ddd-architect.layers', []) as $key => $name) {
            $this->line(sprintf('  <fg=gray>%-18s</> <fg=white>%s</>', $key, $name));
        }

        $this->newLine();

        // ── Stub resolution ────────────────────────────────────────────────
        $this->line('  <fg=white;options=bold>Stub Resolution Order</>');
        $this->newLine();
        $paths   = config('ddd-architect.stub_paths', []);
        $paths[] = $this->renderer->packageStubsPath() . ' <fg=gray>(built-in)</>';
        foreach ($paths as $i => $path) {
            $num   = $i + 1;
            $exists = File::isDirectory(is_string($path) ? explode(' ', $path)[0] : '') ? '<fg=green>✓</>' : '<fg=yellow>✗</>';
            $this->line("  {$exists}  <fg=gray>{$num}.</> {$path}");
        }

        $this->newLine();

        // ── Available commands ─────────────────────────────────────────────
        $this->line('  <fg=white;options=bold>Artisan Commands</>');
        $this->newLine();

        $commands = [
            ['ddd:make:context {context}',          'Scaffold a full bounded context'],
            ['ddd:make:entity {ctx} {name}',         'Generate a Domain Entity'],
            ['ddd:make:value-object {ctx} {name}',   'Generate a Value Object'],
            ['ddd:make:aggregate {ctx} {name}',      'Generate an Aggregate Root'],
            ['ddd:make:event {ctx} {name}',          'Generate a Domain Event'],
            ['ddd:make:service {ctx} {name}',        'Generate a Domain Service'],
            ['ddd:make:specification {ctx} {name}',  'Generate a Specification'],
            ['ddd:make:repository {ctx} {name}',     'Generate Repository Interface + Eloquent impl'],
            ['ddd:make:command {ctx} {name}',        'Generate a CQRS Command + Handler'],
            ['ddd:make:query {ctx} {name}',          'Generate a CQRS Query + Handler'],
            ['ddd:make:dto {ctx} {name}',            'Generate a DTO'],
            ['ddd:list',                             'List all bounded contexts'],
            ['ddd:stubs:publish',                    'Publish stubs for customisation'],
            ['ddd:info',                             'Show this info panel'],
        ];

        foreach ($commands as [$cmd, $desc]) {
            $this->line(sprintf(
                '  <fg=green>%-42s</> <fg=gray>%s</>',
                $cmd,
                $desc
            ));
        }

        $this->newLine();
        $this->line('  <fg=gray>All make commands support --force to overwrite existing files.</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
