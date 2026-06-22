<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Constants\TenantStatus;
use PHPUnit\Framework\TestCase;

final class TenantStatusTest extends TestCase
{
    public function test_only_active_tenants_allow_runtime_access(): void
    {
        foreach (TenantStatus::values() as $status) {
            self::assertSame(
                $status === TenantStatus::ACTIVE,
                TenantStatus::allowsRuntimeAccess($status),
            );
        }
    }
}
