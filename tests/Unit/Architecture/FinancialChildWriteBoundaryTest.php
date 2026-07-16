<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Modules\Invoice\Models\InvoiceBalance;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;
use PHPUnit\Framework\TestCase;

final class FinancialChildWriteBoundaryTest extends TestCase
{
    public function test_authoritative_financial_children_are_deny_by_default(): void
    {
        foreach ([
            new InvoiceLine(),
            new InvoiceBalance(),
            new PaymentLine(),
            new PaymentAllocation(),
        ] as $model) {
            self::assertSame(['*'], $model->getGuarded(), $model::class);
        }
    }
}
