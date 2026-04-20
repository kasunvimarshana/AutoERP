<?php

declare(strict_types=1);

namespace Tests\Unit\Customer;

use Modules\Customer\Domain\Entities\Customer;
use PHPUnit\Framework\TestCase;

class CustomerEntityTest extends TestCase
{
    public function test_constructor_rejects_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Customer(
            tenantId: 9,
            userId: 22,
            name: 'Acme Ltd',
            type: 'partner',
        );
    }

    public function test_update_rejects_negative_payment_terms(): void
    {
        $customer = new Customer(
            tenantId: 9,
            userId: 22,
            name: 'Acme Ltd',
            type: 'company',
        );

        $this->expectException(\InvalidArgumentException::class);

        $customer->update(
            userId: 22,
            customerCode: 'CUS-001',
            name: 'Acme Ltd',
            type: 'company',
            orgUnitId: null,
            taxNumber: null,
            registrationNumber: null,
            currencyId: null,
            creditLimit: '0.000000',
            paymentTermsDays: -1,
            arAccountId: null,
            status: 'active',
            notes: null,
            metadata: null,
        );
    }
}
