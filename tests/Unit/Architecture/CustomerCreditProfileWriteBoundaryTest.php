<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Unit\Architecture;

use Modules\Customer\Models\CustomerCreditProfile;
use PHPUnit\Framework\TestCase;

final class CustomerCreditProfileWriteBoundaryTest extends TestCase
{
    public function test_customer_credit_policy_is_deny_by_default(): void
    {
        self::assertSame(['*'], (new CustomerCreditProfile)->getGuarded());
    }
}
