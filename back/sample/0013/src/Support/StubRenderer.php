<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Support;

use Illuminate\Support\Str;

/**
 * StubRenderer — Loads stub templates and replaces all tokens.
 *
 * Token syntax: {{ tokenName }}  (spaces inside braces are optional)
 *
 * Built-in tokens
 * ───────────────
 * {{ className }}         → PascalCase class name                 e.g. "OrderItem"
 * {{ classSnake }}        → snake_case class name                  e.g. "order_item"
 * {{ classKebab }}        → kebab-case class name                  e.g. "order-item"
 * {{ classCamel }}        → camelCase class name                   e.g. "orderItem"
 * {{ classLower }}        → lower-case class name                  e.g. "orderitem"
 * {{ namespace }}         → Full PHP namespace for the file        e.g. "App\Ordering\Domain\Entities"
 * {{ contextName }}       → PascalCase context name               e.g. "Ordering"
 * {{ contextKebab }}      → kebab-case context name               e.g. "ordering"
 * {{ contextSnake }}      → snake_case context name               e.g. "ordering"
 * {{ contextCamel }}      → camelCase context name                e.g. "ordering"
 * {{ rootNamespace }}     → PSR-4 root namespace                  e.g. "App"
 * {{ date }}              → Current date Y-m-d                    e.g. "2026-01-01"
 * {{ year }}              → Current year                          e.g. "2026"
 * {{ vendor }}            → Composer vendor name from config
 * {{ package }}           → Package name from config
 */
final class StubRenderer
{
    /**
     * Render a stub file by key, replacing all tokens.
     *
     * @param  string  $stubKey   Stub identifier (e.g. "entity", "command")
     * @param  array   $tokens    Map of token => replacement value
     * @return string  Rendered PHP source
     *
     * @throws \RuntimeException when no stub file can be found
     */
    public function render(string $stubKey, array $tokens = []): string
    {
        $stubPath = $this->resolveStubPath($stubKey);
        $contents = file_get_contents($stubPath);

        return $this->replaceTokens($contents, $tokens);
    }

    /**
     * Replace tokens in an arbitrary string (useful for testing).
     */
    public function replaceTokens(string $template, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            // Support both {{ key }} and {{key}} (with or without spaces)
            $template = preg_replace(
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                (string) $value,
                $template
            );
        }

        return $template;
    }

    /**
     * Build a standard token map from context + class name.
     *
     * @param  string  $context    PascalCase context name
     * @param  string  $className  PascalCase class name
     * @param  string  $namespace  Full PHP namespace
     * @param  array   $extra      Additional custom tokens
     * @return array<string, string>
     */
    public function buildTokens(
        string $context,
        string $className,
        string $namespace,
        array  $extra = []
    ): array {
        $mode    = config('ddd-architect.mode', 'layered');
        $nsRoot  = config("ddd-architect.namespaces.{$mode}", 'App');

        return array_merge([
            'className'      => Str::studly($className),
            'classSnake'     => Str::snake($className),
            'classKebab'     => Str::kebab($className),
            'classCamel'     => Str::camel($className),
            'classLower'     => strtolower($className),
            'namespace'      => $namespace,
            'contextName'    => Str::studly($context),
            'contextKebab'   => Str::kebab($context),
            'contextSnake'   => Str::snake($context),
            'contextCamel'   => Str::camel($context),
            'rootNamespace'  => $nsRoot,
            'date'           => date('Y-m-d'),
            'year'           => date('Y'),
        ], $extra);
    }

    /**
     * Resolve the absolute path to a stub file.
     *
     * Resolution order:
     *  1. Paths listed in config('ddd-architect.stub_paths')
     *  2. Package built-in stubs directory
     *
     * @throws \RuntimeException
     */
    public function resolveStubPath(string $stubKey): string
    {
        $filename = ltrim($stubKey, '/') . '.stub';

        // Check user-configured paths first
        $configPaths = config('ddd-architect.stub_paths', []);
        foreach ($configPaths as $dir) {
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Fall back to package built-ins
        $builtin = $this->packageStubsPath() . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($builtin)) {
            return $builtin;
        }

        throw new \RuntimeException("Stub file not found: [{$filename}]. Run `php artisan ddd:stubs:publish` to publish stubs.");
    }

    /**
     * Return the package's built-in stubs directory.
     */
    public function packageStubsPath(): string
    {
        return dirname(__DIR__, 2) . '/stubs';
    }

    /**
     * List all built-in stub keys (without the .stub extension).
     *
     * @return string[]
     */
    public function availableStubs(): array
    {
        $dir   = $this->packageStubsPath();
        $files = glob($dir . '/**/*.stub') ?: [];
        $stubs = [];

        foreach ($files as $file) {
            $stubs[] = str_replace(
                [$dir . '/', '.stub'],
                '',
                $file
            );
        }

        return $stubs;
    }
}
