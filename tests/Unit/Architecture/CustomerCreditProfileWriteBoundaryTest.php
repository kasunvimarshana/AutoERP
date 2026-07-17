<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Modules\Customer\Models\CustomerCreditProfile;
use PHPUnit\Framework\TestCase;

final class CustomerCreditProfileWriteBoundaryTest extends TestCase
{
    public function test_customer_credit_policy_is_deny_by_default(): void
    {
        self::assertSame(['*'], (new CustomerCreditProfile())->getGuarded());
    }
}
