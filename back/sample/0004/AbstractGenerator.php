<?php

namespace YourVendor\LaravelDDDArchitect\Generators;

use YourVendor\LaravelDDDArchitect\Contracts\GeneratorContract;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

/**
 * Abstract base for all DDD file generators.
 *
 * Concrete generators only need to implement:
 *   - stubs()   → list of [stub => targetPath] mappings
 *   - tokens()  → placeholder → value map
 */
abstract class AbstractGenerator implements GeneratorContract
{
    protected array $config;
    protected StubRenderer $renderer;
    protected FileGenerator $files;
    protected bool $force = false;

    public function __construct(array $config, StubRenderer $renderer, FileGenerator $files)
    {
        $this->config   = $config;
        $this->renderer = $renderer;
        $this->files    = $files;
    }

    // -------------------------------------------------------------------------
    // Contract Implementation
    // -------------------------------------------------------------------------

    /**
     * Generate all files returned by stubs(), writing them to disk.
     *
     * @return array<string>  Absolute paths of created files.
     */
    public function generate(): array
    {
        $created = [];
        $tokens  = $this->tokens();

        foreach ($this->stubs() as $stubName => $targetPath) {
            $content = $this->renderer->render($stubName, $tokens);
            $written = $this->files->write($targetPath, $content, $this->force);

            if ($written) {
                $created[] = $targetPath;
            }
        }

        return $created;
    }

    // -------------------------------------------------------------------------
    // Abstract Methods
    // -------------------------------------------------------------------------

    /**
     * Return a map of [stubRelativePath => absoluteTargetPath].
     *
     * @return array<string, string>
     */
    abstract protected function stubs(): array;

    /**
     * Return the token-replacement map used when rendering stubs.
     *
     * @return array<string, string>
     */
    abstract protected function tokens(): array;

    // -------------------------------------------------------------------------
    // Shared Helpers
    // -------------------------------------------------------------------------

    /**
     * Allow overwriting existing files.
     */
    public function force(bool $force = true): static
    {
        $this->force = $force;
        return $this;
    }

    /**
     * Resolve the absolute base path for a bounded context's DDD layer.
     *
     * e.g. resolveLayerPath('Order', 'Domain', 'Entities')
     *      → /var/www/app/Domain/Order/Entities
     */
    protected function resolveLayerPath(string $context, string ...$segments): string
    {
        $base = base_path($this->config['base_path']);
        return implode(DIRECTORY_SEPARATOR, [$base, ...$segments]);
    }

    /**
     * Resolve the PHP namespace for a given path segment.
     */
    protected function resolveNamespace(string ...$segments): string
    {
        $root = rtrim($this->config['namespace'], '\\');
        return implode('\\', [$root, ...$segments]);
    }

    /**
     * Shortcut to get the root application namespace.
     */
    protected function rootNamespace(): string
    {
        return rtrim($this->config['namespace'], '\\');
    }
}
