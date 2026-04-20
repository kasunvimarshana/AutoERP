<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ModuleBoundaryGuardrailsTest extends TestCase
{
    private string $modulesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesRoot = dirname(__DIR__, 3).'/app/Modules';
    }

    public function test_core_value_objects_remain_minimal_and_foundational(): void
    {
        $valueObjectDir = $this->modulesRoot.'/Core/Domain/ValueObjects';
        $files = [];

        foreach (scandir($valueObjectDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (! is_file($valueObjectDir.'/'.$entry)) {
                continue;
            }

            $files[] = $entry;
        }

        sort($files);

        $this->assertSame(
            ['.gitkeep', 'ValueObject.php'],
            $files,
            'Core/Domain/ValueObjects must only keep foundational abstractions. Move domain-specific VOs to owning modules.'
        );
    }

    public function test_domain_layer_does_not_import_infrastructure_layer(): void
    {
        $violations = [];

        foreach ($this->allPhpFiles($this->modulesRoot) as $filePath) {
            $normalizedPath = str_replace('\\', '/', $filePath);

            if (! str_contains($normalizedPath, '/Domain/')) {
                continue;
            }

            foreach ($this->extractUseStatements(file_get_contents($filePath) ?: '') as $useStatement) {
                if (! str_starts_with($useStatement, 'Modules\\')) {
                    continue;
                }

                if (str_contains($useStatement, '\\Infrastructure\\')) {
                    $violations[] = $normalizedPath.' -> '.$useStatement;
                }
            }
        }

        $this->assertSame([], $violations, "Domain layer must not import Infrastructure namespaces.\n".implode("\n", $violations));
    }

    public function test_application_layer_does_not_import_infrastructure_layer(): void
    {
        $violations = [];

        foreach ($this->allPhpFiles($this->modulesRoot) as $filePath) {
            $normalizedPath = str_replace('\\', '/', $filePath);

            if (! str_contains($normalizedPath, '/Application/')) {
                continue;
            }

            foreach ($this->extractUseStatements(file_get_contents($filePath) ?: '') as $useStatement) {
                if (str_contains($useStatement, '\\Infrastructure\\')) {
                    $violations[] = $normalizedPath.' -> '.$useStatement;
                }
            }
        }

        $this->assertSame([], $violations, "Application layer must not import Infrastructure namespaces.\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function allPhpFiles(string $rootPath): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            if ($fileInfo->getExtension() !== 'php') {
                continue;
            }

            $files[] = $fileInfo->getPathname();
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function extractUseStatements(string $contents): array
    {
        preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

        return array_values(array_map(static fn (string $statement): string => trim($statement), $matches[1] ?? []));
    }
}
