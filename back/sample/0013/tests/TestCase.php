<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests;

use Archify\DddArchitect\Providers\DddArchitectServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base TestCase for all DDD Architect package tests.
 *
 * Uses Orchestra Testbench to boot a minimal Laravel application so
 * the service provider, config, and container bindings are available.
 */
abstract class TestCase extends OrchestraTestCase
{
    protected Filesystem $files;

    /** Temporary directory used during tests — cleaned up after each test. */
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files   = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/ddd-architect-tests-' . uniqid();
        $this->files->ensureDirectoryExists($this->tempDir);

        // Point all config paths into the temp directory
        config([
            'ddd-architect.mode'                 => 'layered',
            'ddd-architect.paths.layered'        => $this->tempDir . '/src',
            'ddd-architect.namespaces.layered'   => 'App',
            'ddd-architect.shared_kernel.path'   => $this->tempDir . '/src/Shared',
            'ddd-architect.shared_kernel.namespace' => 'App\\Shared',
            'ddd-architect.stub_paths'           => [],   // use built-in stubs only
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->files->isDirectory($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [DddArchitectServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'DddArchitect' => \Archify\DddArchitect\Facades\DddArchitect::class,
        ];
    }

    /** Assert that a file was created at the given absolute path. */
    protected function assertFileWasCreated(string $path): void
    {
        $this->assertFileExists($path, "Expected file to be created at: {$path}");
    }

    /** Assert a file contains the given string. */
    protected function assertFileContains(string $path, string $needle): void
    {
        $this->assertStringContainsString(
            $needle,
            file_get_contents($path),
            "File [{$path}] does not contain [{$needle}]."
        );
    }
}
