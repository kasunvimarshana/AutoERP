<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Support\StubRenderer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * PublishStubsCommand — Publish all built-in stubs to the application.
 *
 * Usage:
 *   php artisan ddd:stubs:publish
 *   php artisan ddd:stubs:publish --force
 *
 * After publishing, edit any stub in:
 *   resources/stubs/ddd/
 *
 * The package will automatically prefer your custom version over the built-in default.
 */
final class PublishStubsCommand extends Command
{
    protected $signature   = 'ddd:stubs:publish
        {--force : Overwrite already-published stubs}';
    protected $description = 'Publish all DDD Architect stub templates for customisation';

    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly Filesystem   $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $source      = $this->renderer->packageStubsPath();
        $destination = resource_path('stubs/ddd');
        $force       = (bool) $this->option('force');

        $this->newLine();
        $this->line('  <fg=blue;options=bold>DDD Architect</> · Publishing stubs');
        $this->newLine();

        $this->files->ensureDirectoryExists($destination);

        $published = 0;
        $skipped   = 0;

        /** @var \SplFileInfo $file */
        foreach ($this->files->allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $target   = $destination . DIRECTORY_SEPARATOR . $relative;

            if ($this->files->exists($target) && ! $force) {
                $this->line("  <fg=yellow>SKIP</>  {$relative}");
                $skipped++;
                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->copy($file->getPathname(), $target);
            $this->line("  <fg=green>PUBLISHED</>  {$relative}");
            $published++;
        }

        $this->newLine();
        $this->line("  <fg=green;options=bold>{$published} stub(s) published</> · <fg=yellow>{$skipped} skipped</>");
        $this->newLine();
        $this->line('  <fg=gray>Stubs published to: ' . $destination . '</>');
        $this->newLine();

        if ($skipped > 0 && ! $force) {
            $this->line('  <fg=gray>Tip: use --force to overwrite existing stubs.</>');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
