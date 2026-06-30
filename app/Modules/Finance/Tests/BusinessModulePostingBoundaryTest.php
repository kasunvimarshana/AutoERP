<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class BusinessModulePostingBoundaryTest extends TestCase
{
    public function test_business_modules_do_not_select_finance_accounts_by_code(): void
    {
        $modulesRoot = dirname(__DIR__, 2);
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulesRoot, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR.'Finance'.DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);
            if (str_contains($source, 'accountCode:')) {
                $violations[] = str_replace($modulesRoot.DIRECTORY_SEPARATOR, '', $path);
            }
        }

        self::assertSame([], $violations, 'Business posting producers must use semantic profileKey mappings: '.implode(', ', $violations));
    }
}
