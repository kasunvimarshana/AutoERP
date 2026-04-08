<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Contracts\GeneratorContract;
use Archify\DddArchitect\Support\ContextResolver;
use Archify\DddArchitect\Support\GenerationResult;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * AbstractDddCommand — Base class for all DDD Artisan generator commands.
 *
 * Provides shared argument parsing, coloured console output, and the
 * generate() orchestration loop. Concrete commands only need to declare
 * their signature, description, and the generator(s) they delegate to.
 *
 * -------------------------------------------------------------------------
 * Extension Guide
 * -------------------------------------------------------------------------
 *
 * To create a custom Artisan DDD command:
 *
 *   1. Extend this class.
 *   2. Set $signature and $description.
 *   3. Implement generators() to return one or more GeneratorContract instances.
 *   4. Register the command in your ServiceProvider (or let auto-discovery handle it).
 *
 * Example:
 *
 *   class MakePolicyCommand extends AbstractDddCommand
 *   {
 *       protected $signature   = 'ddd:make:policy {context} {name} {--force}';
 *       protected $description = 'Generate a Domain Policy class';
 *
 *       protected function generators(): array
 *       {
 *           return [app(PolicyGenerator::class)];
 *       }
 *   }
 */
abstract class AbstractDddCommand extends Command
{
    // -------------------------------------------------------------------------
    // Abstract hook — implement in each concrete command
    // -------------------------------------------------------------------------

    /**
     * Return the generator(s) this command delegates to.
     *
     * @return GeneratorContract[]
     */
    abstract protected function generators(): array;

    // -------------------------------------------------------------------------
    // Command entry point
    // -------------------------------------------------------------------------

    public function handle(): int
    {
        $context   = Str::studly($this->argument('context'));
        $name      = Str::studly($this->argument('name'));
        $force     = (bool) $this->option('force');

        $this->printHeader($context, $name);

        $anyFailed = false;

        foreach ($this->generators() as $generator) {
            $succeeded = $generator->generate($context, $name, ['force' => $force]);

            if (! $succeeded) {
                $anyFailed = true;
                $this->printSkipped($generator->label());
            } else {
                $this->printCreated($generator->label(), $context, $name);
            }
        }

        $this->newLine();

        return $anyFailed ? self::FAILURE : self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Console output helpers
    // -------------------------------------------------------------------------

    protected function printHeader(string $context, string $name): void
    {
        $this->newLine();
        $this->line("  <fg=blue;options=bold>DDD Architect</> · <fg=cyan>{$context}</> context");
        $this->newLine();
    }

    protected function printCreated(string $label, string $context, string $name): void
    {
        $this->line("  <fg=green;options=bold>✓ CREATED</> <fg=white>{$label}</> <fg=gray>→ {$context}/{$name}</>  ");
    }

    protected function printSkipped(string $label): void
    {
        $this->line("  <fg=yellow;options=bold>⚠ SKIPPED</> <fg=white>{$label}</> <fg=gray>(already exists — use --force to overwrite)</>  ");
    }

    protected function printInfo(string $message): void
    {
        $this->line("  <fg=blue>ℹ</> {$message}");
    }
}
