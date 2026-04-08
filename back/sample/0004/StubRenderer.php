<?php

namespace YourVendor\LaravelDDDArchitect\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Resolves stub templates (preferring app-published stubs over package defaults)
 * and renders them by replacing placeholder tokens with real values.
 */
class StubRenderer
{
    /**
     * @param string $appStubPath  Path where published stubs live (customisable)
     * @param string $pkgStubPath  Fallback path inside the package
     */
    public function __construct(
        protected string $appStubPath,
        protected string $pkgStubPath,
    ) {
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Render a named stub, replacing all tokens with provided values.
     *
     * @param  string  $stub    Relative stub path (e.g. "domain/entity.stub")
     * @param  array   $tokens  Key/value pairs to replace in the stub content
     * @return string           Rendered PHP source
     */
    public function render(string $stub, array $tokens = []): string
    {
        $content = File::get($this->resolve($stub));
        return $this->replace($content, $tokens);
    }

    /**
     * Resolve the absolute path of a stub, preferring app-published overrides.
     */
    public function resolve(string $stub): string
    {
        $appPath = rtrim($this->appStubPath, '/') . '/' . $stub;
        if (File::exists($appPath)) {
            return $appPath;
        }

        $pkgPath = rtrim($this->pkgStubPath, '/') . '/' . $stub;
        if (File::exists($pkgPath)) {
            return $pkgPath;
        }

        throw new \RuntimeException("Stub [{$stub}] not found in [{$this->appStubPath}] or [{$this->pkgStubPath}].");
    }

    /**
     * Replace all {{ token }} placeholders in content.
     */
    public function replace(string $content, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            // Support both {{ Key }} and {{Key}} variants
            $content = str_replace(
                ["{{ {$key} }}", "{{{$key}}}"],
                $value,
                $content
            );
        }
        return $content;
    }

    // -------------------------------------------------------------------------
    // Token Builders
    // -------------------------------------------------------------------------

    /**
     * Build a standard token map for a given class inside a bounded context.
     *
     * @param  string  $context    e.g. "Order"
     * @param  string  $className  e.g. "Order" (for OrderEntity → Entity layer)
     * @param  string  $layer      e.g. "Domain\Order\Entities"
     * @param  string  $rootNs     e.g. "App"
     */
    public static function buildTokens(
        string $context,
        string $className,
        string $layer,
        string $rootNs = 'App',
    ): array {
        return [
            'rootNamespace'   => $rootNs,
            'contextName'     => Str::studly($context),
            'contextLower'    => Str::lower($context),
            'contextSnake'    => Str::snake($context),
            'contextKebab'    => Str::kebab($context),
            'className'       => Str::studly($className),
            'classLower'      => Str::lower($className),
            'classSnake'      => Str::snake($className),
            'classKebab'      => Str::kebab($className),
            'namespace'       => "{$rootNs}\\{$layer}",
            'layerPath'       => $layer,
            'year'            => now()->year,
            'date'            => now()->format('Y_m_d'),
            'timestamp'       => now()->format('Y_m_d_His'),
        ];
    }
}
