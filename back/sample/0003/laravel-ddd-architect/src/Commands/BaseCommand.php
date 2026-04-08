<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Console\Command;
use YourVendor\LaravelDDDArchitect\Contracts\ContextRegistrar;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

abstract class BaseCommand extends Command
{
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

    /**
     * Print a list of created file paths to the console.
     *
     * @param  array<string>  $paths
     */
    protected function reportCreated(array $paths): void
    {
        foreach ($paths as $path) {
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $this->components->twoColumnDetail(
                "<fg=green;options=bold>CREATED</>",
                $relative
            );
        }
    }

    /**
     * Print an already-exists notice.
     */
    protected function reportSkipped(string $path): void
    {
        $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
        $this->components->twoColumnDetail(
            "<fg=yellow>SKIPPED</>",
            "{$relative} (already exists)"
        );
    }
}
