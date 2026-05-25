<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class GlobalArchitectureContractsTest extends TestCase
{
    private string $modulesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesRoot = dirname(__DIR__, 3) . '/app/Modules';
    }

    public function testNoDirectEloquentUsageOutsideInfrastructure(): void
    {
        $violations = [];

        foreach ($this->phpFiles($this->modulesRoot) as $filePath) {
            if ($this->isInfrastructureFile($filePath)) {
                continue;
            }

            $content = (string) file_get_contents($filePath);

            if (preg_match('/use\\s+Illuminate\\\\Database\\\\Eloquent\\\\/i', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' imports Eloquent outside Infrastructure';
            }

            if (preg_match('/extends\\s+Model\\b/', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' extends Model outside Infrastructure';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function testDomainLayerHasNoUpwardDependencies(): void
    {
        $violations = [];

        foreach ($this->phpFiles($this->modulesRoot) as $filePath) {
            if (strpos($this->normalizePath($filePath), '/Domain/') === false) {
                continue;
            }

            $content = (string) file_get_contents($filePath);

            if (preg_match('/use\\s+Modules\\\\[^\\\\]+\\\\Application\\\\/i', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' depends on Application layer';
            }

            if (preg_match('/use\\s+Modules\\\\[^\\\\]+\\\\Infrastructure\\\\/i', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' depends on Infrastructure layer';
            }

            if (preg_match('/use\\s+Modules\\\\[^\\\\]+\\\\Presentation\\\\/i', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' depends on Presentation layer';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function testPresentationLayerUsesUseCaseInterfacesOnly(): void
    {
        $violations = [];

        foreach ($this->phpFiles($this->modulesRoot) as $filePath) {
            $normalized = $this->normalizePath($filePath);
            $isPresentation = strpos($normalized, '/Presentation/') !== false;
            $isControllerOrCommand = preg_match('/(Controller|Command)\\.php$/', basename($filePath)) === 1;

            if (!$isPresentation || !$isControllerOrCommand) {
                continue;
            }

            $content = (string) file_get_contents($filePath);

            if (
                preg_match(
                    '/use\\s+Modules\\\\[^\\\\]+\\\\Application\\\\UseCases\\\\[^;]*Service\\s*;/i',
                    $content
                ) === 1
            ) {
                $violations[] = $this->relativePath($filePath) . ' imports concrete use-case service in Presentation';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function testApplicationUseCasesDoNotImportInfrastructure(): void
    {
        $violations = [];

        foreach ($this->phpFiles($this->modulesRoot) as $filePath) {
            $normalized = $this->normalizePath($filePath);
            if (strpos($normalized, '/Application/UseCases/') === false) {
                continue;
            }

            $content = (string) file_get_contents($filePath);
            if (preg_match('/use\\s+Modules\\\\[^\\\\]+\\\\Infrastructure\\\\/i', $content) === 1) {
                $violations[] = $this->relativePath($filePath) . ' imports Infrastructure in Application UseCase';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function testOneClassPerFileConvention(): void
    {
        $violations = [];

        foreach ($this->phpFiles($this->modulesRoot) as $filePath) {
            $content = (string) file_get_contents($filePath);

            if (preg_match('/\\breturn\\s+new\\s+class\\b/', $content) === 1) {
                continue;
            }

            $tokenCount = preg_match_all(
                '/\\b(class|interface|trait|enum)\\s+[A-Za-z_][A-Za-z0-9_]*/',
                $content,
                $matches
            );

            if ($tokenCount !== false && $tokenCount > 1) {
                $violations[] = $this->relativePath($filePath) . ' contains multiple class-like declarations';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $this->normalizePath($file->getPathname());

            if (strpos($path, '/vendor/') !== false || strpos($path, '/back/') !== false) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function isInfrastructureFile(string $filePath): bool
    {
        return strpos($this->normalizePath($filePath), '/Infrastructure/') !== false;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function relativePath(string $path): string
    {
        $root = $this->normalizePath(dirname(__DIR__, 3));
        $normalized = $this->normalizePath($path);

        if (str_starts_with($normalized, $root . '/')) {
            return substr($normalized, strlen($root) + 1);
        }

        return $normalized;
    }
}
