<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Tests;

use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use PHPUnit\Framework\TestCase;

final class OrganizationUnitPermissionTest extends TestCase
{
    public function test_every_permission_has_a_unique_human_readable_description(): void
    {
        $definitions = OrganizationUnitPermission::descriptions();

        self::assertCount(10, $definitions);
        self::assertCount(count($definitions), array_unique(array_keys($definitions)));
        foreach ($definitions as $permission => $description) {
            self::assertMatchesRegularExpression('/^organization-unit(?:s|-types|-documents)\.[a-z-]+$/', $permission);
            self::assertNotSame('', trim($description));
        }
    }
}
