<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Console\Command;
use YourVendor\LaravelDDDArchitect\Contracts\ContextRegistrar;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

abstract class BaseCommand extends Command
{
    // -------------------------------------------------------------------------
    // Container resolution helpers
    // -------------------------------------------------------------------------

    protected function registrar(): ContextRegistrar
    {
        return $this->laravel->make(ContextRegistrar::class);
    }

    protected function renderer(): StubRenderer
    {
        return $this->laravel->make(StubRenderer::class);
    }

    protected function fileGen(): FileGenerator
    {
        return $this->laravel->make(FileGenerator::class);
    }

    protected function config(): array
    {
        return config('ddd-architect');
    }

    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    /**
     * Print a "CREATED" row for each generated file path.
     *
     * @param  array<string>  $paths
     */
    protected function reportCreated(array $paths): void
    {
        foreach ($paths as $path) {
            $relative = ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
            $this->components->twoColumnDetail(
                '<fg=green;options=bold>CREATED</>',
                $relative
            );
        }
    }

    /**
     * Print a "SKIPPED" row for an already-existing file.
     */
    protected function reportSkipped(string $path): void
    {
        $relative = ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
        $this->components->twoColumnDetail(
            '<fg=yellow>SKIPPED</>',
            "{$relative} (already exists — use --force to overwrite)"
        );
    }
}
