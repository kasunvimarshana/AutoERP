<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Constants\TenantPermission;
use PHPUnit\Framework\TestCase;

final class TenantPermissionTest extends TestCase
{
    public function test_permission_catalogue_has_unique_canonical_names(): void
    {
        $permissions = array_keys(TenantPermission::descriptions());

        self::assertSame($permissions, array_values(array_unique($permissions)));
        foreach ($permissions as $permission) {
            self::assertMatchesRegularExpression('/^[a-z][a-z0-9_.]+$/', $permission);
        }
    }
}
