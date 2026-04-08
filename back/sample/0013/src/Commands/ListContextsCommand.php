<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Contracts\ContextRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * ListContextsCommand — List all discovered bounded contexts.
 *
 * Usage:
 *   php artisan ddd:list
 */
final class ListContextsCommand extends Command
{
    protected $signature   = 'ddd:list';
    protected $description = 'List all discovered bounded contexts';

    public function handle(ContextRegistrar $registrar): int
    {
        $this->newLine();
        $this->line('  <fg=blue;options=bold>DDD Architect</> · Bounded Contexts');
        $this->newLine();

        // Also scan filesystem for any contexts not yet registered
        $mode     = config('ddd-architect.mode', 'layered');
        $basePath = config("ddd-architect.paths.{$mode}");

        $scanned = [];
        if (File::isDirectory($basePath)) {
            foreach (File::directories($basePath) as $dir) {
                $scanned[] = basename($dir);
            }
        }

        $registered = array_keys($registrar->all());
        $all        = array_unique(array_merge($registered, $scanned));
        sort($all);

        if (empty($all)) {
            $this->line('  <fg=yellow>No bounded contexts found.</> Run <fg=white>php artisan ddd:make:context {Name}</> to create one.');
            $this->newLine();
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($all as $context) {
            $contextPath     = rtrim($basePath, '/') . '/' . $context;
            $providerClass   = $registrar->get($context)['provider'] ?? '—';
            $isRegistered    = in_array($context, $registered, true) ? '<fg=green>✓</>' : '<fg=gray>discovered</>';

            $rows[] = [
                "<fg=cyan>{$context}</>",
                $isRegistered,
                "<fg=gray>{$contextPath}</>",
            ];
        }

        $this->table(
            ['<fg=white;options=bold>Context</>', '<fg=white;options=bold>Status</>', '<fg=white;options=bold>Path</>'],
            $rows
        );

        $this->newLine();
        $this->line("  <fg=gray>Total: " . count($all) . " context(s) · Mode: {$mode}</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
