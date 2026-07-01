<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TenantExistenceDisclosureTest extends TestCase
{
    public function test_tenant_application_code_does_not_probe_foreign_ownership_to_distinguish_missing_ids(): void
    {
        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/Database/Migrations/')
                || str_contains($path, '/Database/Seeders/')
                || str_contains($path, '/Tests/')) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            foreach ([
                "where('tenant_id', '<>'",
                'where("tenant_id", "<>"',
                "where('tenant_id', '!='",
                'where("tenant_id", "!="',
            ] as $probe) {
                if (str_contains($source, $probe)) {
                    $violations[] = "{$path}: contains cross-tenant existence probe {$probe}";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
