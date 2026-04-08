<?php

namespace YourVendor\LaravelDDDArchitect\Resolvers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Contracts\ContextRegistrar;

class ContextResolver implements ContextRegistrar
{
    public function __construct(protected array $config)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        $domainBase = $this->domainBasePath();

        if (! File::isDirectory($domainBase)) {
            return [];
        }

        return collect(File::directories($domainBase))
            ->map(fn ($dir) => basename($dir))
            ->reject(fn ($name) => $name === 'Shared')
            ->values()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $context): bool
    {
        return File::isDirectory($this->path($context));
    }

    /**
     * {@inheritDoc}
     */
    public function path(string $context): string
    {
        return $this->domainBasePath() . DIRECTORY_SEPARATOR . Str::studly($context);
    }

    /**
     * {@inheritDoc}
     */
    public function namespace(string $context): string
    {
        $root = rtrim($this->config['namespace'], '\\');
        return "{$root}\\Domain\\" . Str::studly($context);
    }

    /**
     * {@inheritDoc}
     */
    public function config(): array
    {
        return $this->config;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function domainBasePath(): string
    {
        return base_path($this->config['base_path']) . DIRECTORY_SEPARATOR . 'Domain';
    }
}
