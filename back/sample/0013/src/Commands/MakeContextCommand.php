<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\ContextScaffoldGenerator;
use Archify\DddArchitect\Generators\SharedKernelGenerator;

/**
 * MakeContextCommand — Scaffold a complete bounded context directory tree.
 *
 * Usage:
 *   php artisan ddd:make:context Ordering
 *   php artisan ddd:make:context Ordering --force
 *
 * What it creates:
 *   • Full directory tree for Domain / Application / Infrastructure / Presentation layers
 *   • {Context}ServiceProvider stub in Infrastructure/Providers/
 *   • Shared Kernel (contracts + value objects) on first run (if not already present)
 */
final class MakeContextCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:context
        {context        : Bounded context name in PascalCase (e.g. Ordering)}
        {name?          : Ignored — reserved for base class compatibility}
        {--force        : Overwrite existing files}';

    protected $description = 'Scaffold a complete DDD bounded context directory tree';

    protected function generators(): array
    {
        return [
            app(SharedKernelGenerator::class),
            app(ContextScaffoldGenerator::class),
        ];
    }

    public function handle(): int
    {
        $context = $this->argument('context');
        $force   = (bool) $this->option('force');

        $this->newLine();
        $this->line("  <fg=blue;options=bold>DDD Architect</> · Scaffolding context <fg=cyan>{$context}</>");
        $this->newLine();

        // Scaffold shared kernel (no-op if already exists and not forced)
        app(SharedKernelGenerator::class)->generate($context, $context, ['force' => $force]);
        $this->line("  <fg=green;options=bold>✓</> Shared Kernel");

        // Scaffold the context tree + provider
        app(ContextScaffoldGenerator::class)->generate($context, $context, ['force' => $force]);
        $this->line("  <fg=green;options=bold>✓</> Bounded context tree");
        $this->line("  <fg=green;options=bold>✓</> {$context}ServiceProvider stub");

        $this->newLine();
        $this->line("  <fg=green>Context <fg=white;options=bold>{$context}</> scaffolded successfully!</>");
        $this->newLine();

        $mode    = config('ddd-architect.mode', 'layered');
        $basePath = config("ddd-architect.paths.{$mode}");
        $this->line("  <fg=gray>Path → {$basePath}/{$context}</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
