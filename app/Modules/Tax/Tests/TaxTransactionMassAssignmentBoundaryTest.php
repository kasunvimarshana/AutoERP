<?php

declare(strict_types=1);

namespace Modules\Tax\Tests;

use Modules\Tax\Models\TaxTransaction;
use PHPUnit\Framework\TestCase;

final class TaxTransactionMassAssignmentBoundaryTest extends TestCase
{
    public function test_tax_transaction_is_totally_guarded_and_owner_service_writes_explicitly(): void
    {
        $transaction = new TaxTransaction();
        $service = file_get_contents(dirname(__DIR__).'/Services/TaxSnapshotService.php');

        self::assertSame(['*'], $transaction->getGuarded());
        self::assertFalse($transaction->isFillable('tax_amount'));
        self::assertIsString($service);
        self::assertStringContainsString('$transaction = new TaxTransaction();', $service);
        self::assertStringContainsString('$transaction->forceFill([', $service);
        self::assertStringNotContainsString('TaxTransaction::query()->create([', $service);
    }
}
