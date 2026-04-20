<?php

declare(strict_types=1);

namespace Tests\Unit\Supplier;

use Modules\Supplier\Domain\Entities\Supplier;
use PHPUnit\Framework\TestCase;

class SupplierEntityTest extends TestCase
{
    public function test_constructor_rejects_invalid_status(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Supplier(
            tenantId: 9,
            userId: 22,
            name: 'Acme Supplies',
            type: 'company',
            status: 'suspended',
        );
    }

    public function test_update_rejects_negative_payment_terms(): void
    {
        $supplier = new Supplier(
            tenantId: 9,
            userId: 22,
            name: 'Acme Supplies',
            type: 'company',
        );

        $this->expectException(\InvalidArgumentException::class);

        $supplier->update(
            userId: 22,
            supplierCode: 'SUP-001',
            name: 'Acme Supplies',
            type: 'company',
            orgUnitId: null,
            taxNumber: null,
            registrationNumber: null,
            currencyId: null,
            paymentTermsDays: -1,
            apAccountId: null,
            status: 'active',
            notes: null,
            metadata: null,
        );
    }
}
