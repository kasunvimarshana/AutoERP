<?php

namespace YourVendor\LaravelDDDArchitect\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use YourVendor\LaravelDDDArchitect\Providers\DDDArchitectServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [DDDArchitectServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'DDDArchitect' => \YourVendor\LaravelDDDArchitect\Facades\DDDArchitect::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ddd-architect.base_path', 'app');
        $app['config']->set('ddd-architect.namespace', 'App');
        $app['config']->set('ddd-architect.mode', 'full');
        $app['config']->set('ddd-architect.auto_discover', false);
    }

    /**
     * Clean up any generated files after each test.
     */
    protected function tearDown(): void
    {
        $this->cleanGeneratedFiles();
        parent::tearDown();
    }

    protected function cleanGeneratedFiles(): void
    {
        $paths = [
            base_path('app/Domain'),
            base_path('app/Application'),
            base_path('app/Infrastructure'),
            base_path('app/Presentation'),
            base_path('tests/Unit/TestContext'),
            base_path('tests/Feature/TestContext'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
