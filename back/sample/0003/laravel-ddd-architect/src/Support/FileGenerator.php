<?php

namespace YourVendor\LaravelDDDArchitect\Support;

use Illuminate\Support\Facades\File;

/**
 * Responsible for writing rendered content to the filesystem safely.
 */
class FileGenerator
{
    /**
     * Write $content to $path, creating parent directories as needed.
     *
     * @param  string  $path     Absolute path to target file
     * @param  string  $content  File content to write
     * @param  bool    $force    Overwrite existing file if true
     * @return bool              True when file was written, false when skipped (exists + !force)
     */
    public function write(string $path, string $content, bool $force = false): bool
    {
        if (File::exists($path) && ! $force) {
            return false;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        return true;
    }

    /**
     * Ensure a directory exists (and optionally add a .gitkeep placeholder).
     */
    public function ensureDirectory(string $path, bool $gitkeep = true): void
    {
        File::ensureDirectoryExists($path, 0755, true);

        if ($gitkeep && ! File::exists($path . '/.gitkeep')) {
            File::put($path . '/.gitkeep', '');
        }
    }

    /**
     * Create multiple directories at once.
     *
     * @param  array<string>  $paths
     */
    public function ensureDirectories(array $paths, bool $gitkeep = true): void
    {
        foreach ($paths as $path) {
            $this->ensureDirectory($path, $gitkeep);
        }
    }
}
