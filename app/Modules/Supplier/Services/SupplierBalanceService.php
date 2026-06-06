<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Supplier\DTOs\SupplierBalanceResult;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierBalance;

final class SupplierBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function createInitial(Supplier $supplier, string $openingBalance = '0.000000'): SupplierBalance
    {
        $opening = $this->math->normalize($openingBalance);

        return SupplierBalance::query()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => $supplier->getKey(),
            'opening_balance' => $opening,
            'invoice_total' => '0.000000',
            'payment_total' => '0.000000',
            'credit_total' => '0.000000',
            'debit_total' => '0.000000',
            'outstanding_balance' => $opening,
        ]);
    }

    public function result(Supplier $supplier): SupplierBalanceResult
    {
        $balance = $supplier->balance()->firstOrFail();

        return new SupplierBalanceResult(
            supplierId: (int) $supplier->getKey(),
            openingBalance: (string) $balance->opening_balance,
            invoiceTotal: (string) $balance->invoice_total,
            paymentTotal: (string) $balance->payment_total,
            creditTotal: (string) $balance->credit_total,
            debitTotal: (string) $balance->debit_total,
            outstandingBalance: (string) $balance->outstanding_balance,
            lastTransactionDate: $balance->last_transaction_date?->toDateString(),
        );
    }
}
