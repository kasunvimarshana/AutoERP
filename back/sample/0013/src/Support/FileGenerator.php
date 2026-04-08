<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * FileGenerator — Safely writes generated PHP files to disk.
 *
 * Respects the --force flag: without it, existing files are never
 * silently overwritten. Optionally creates a .bak backup before
 * overwriting when config('ddd-architect.generator.backup_on_overwrite') is true.
 */
final class FileGenerator
{
    public function __construct(
        private readonly Filesystem $files
    ) {}

    /**
     * Write $content to $targetPath.
     *
     * @param  string  $targetPath  Absolute filesystem path
     * @param  string  $content     PHP source to write
     * @param  bool    $force       Overwrite existing files when true
     * @return GenerationResult
     */
    public function write(string $targetPath, string $content, bool $force = false): GenerationResult
    {
        // Dry-run mode — report without writing
        if (config('ddd-architect.generator.dry_run', false)) {
            return GenerationResult::dryRun($targetPath);
        }

        // File exists and force is off → skip
        if ($this->files->exists($targetPath) && ! $force) {
            return GenerationResult::skipped($targetPath);
        }

        // Optionally back up the existing file
        if ($this->files->exists($targetPath) && config('ddd-architect.generator.backup_on_overwrite', false)) {
            $this->files->copy($targetPath, $targetPath . '.bak');
        }

        // Ensure parent directories exist
        $this->files->ensureDirectoryExists(dirname($targetPath));

        $this->files->put($targetPath, $content);

        return GenerationResult::created($targetPath);
    }

    /**
     * Create an empty directory (no-op if it already exists).
     */
    public function ensureDirectory(string $path): void
    {
        $this->files->ensureDirectoryExists($path);
    }
}
