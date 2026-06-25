<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Tests;

use PHPUnit\Framework\TestCase;

final class OrganizationUnitMigrationContractTest extends TestCase
{
    public function test_hierarchy_uses_a_fixed_size_unique_key_instead_of_indexing_the_full_path(): void
    {
        $source = $this->migration('2026_06_12_020002_create_organization_units_table.php');

        self::assertStringContainsString("char('path_hash', 64)", $source);
        self::assertStringContainsString("unique(['tenant_id', 'path_hash']", $source);
        self::assertStringNotContainsString("unique(['tenant_id', 'path']", $source);
        self::assertStringContainsString('restrictOnDelete()', $source);
        self::assertStringNotContainsString('softDeletes()', $source);
        self::assertStringNotContainsString('image_path', $source);
    }

    public function test_type_and_document_uniqueness_are_case_insensitive_and_portable(): void
    {
        $types = $this->migration('2026_06_12_020001_create_organization_unit_types_table.php');
        $documents = $this->migration('2026_06_12_020005_create_organization_unit_documents_table.php');

        self::assertStringContainsString("char('name_key', 64)", $types);
        self::assertStringContainsString("unique(['tenant_id', 'name_key']", $types);
        self::assertStringContainsString("char('active_name_hash', 64)", $documents);
        self::assertStringContainsString("string('object_key')", $documents);
        self::assertStringNotContainsString('file_path', $documents);
    }

    private function migration(string $filename): string
    {
        $source = file_get_contents(dirname(__DIR__).'/Database/Migrations/'.$filename);
        self::assertIsString($source);

        return $source;
    }
}
