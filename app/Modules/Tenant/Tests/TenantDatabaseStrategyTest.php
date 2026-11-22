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

use Modules\Tenant\Constants\TenantDatabaseStrategy;
use PHPUnit\Framework\TestCase;

final class TenantDatabaseStrategyTest extends TestCase
{
    public function test_shared_schema_is_the_only_supported_database_strategy(): void
    {
        self::assertSame(
            [TenantDatabaseStrategy::SHARED_SCHEMA],
            TenantDatabaseStrategy::supported(),
        );
    }
}
