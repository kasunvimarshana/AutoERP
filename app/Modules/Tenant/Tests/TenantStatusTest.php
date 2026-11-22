<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
