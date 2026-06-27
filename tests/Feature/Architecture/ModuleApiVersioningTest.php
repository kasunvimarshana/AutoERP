<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ModuleApiVersioningTest extends TestCase
{
    public function test_module_api_route_prefixes_are_versioned(): void
    {
        $moduleDirectory = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDirectory));
        $routeFiles = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (str_ends_with($file->getPathname(), DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php')) {
                $routeFiles[] = $file->getPathname();
            }
        }

        self::assertNotSame([], $routeFiles, 'No module API route files were discovered.');

        foreach ($routeFiles as $routeFile) {
            $source = (string) file_get_contents($routeFile);
            preg_match_all('/Route::prefix\([\'"](api\/[^\'"]+)[\'"]\)/', $source, $matches);

            foreach ($matches[1] ?? [] as $prefix) {
                self::assertStringStartsWith(
                    'api/v1',
                    $prefix,
                    $routeFile.' contains an unversioned application API prefix: '.$prefix,
                );
            }
        }
    }
}
