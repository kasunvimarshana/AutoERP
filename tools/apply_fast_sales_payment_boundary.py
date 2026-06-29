from __future__ import annotations

from hashlib import sha1
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]

EXPECTED_BLOBS = {
    "app/Modules/Sales/Services/FastSalesService.php": "6c8ab1dbc0bd0be6f927807b2045afaa60ba882b",
    "app/Modules/Sales/Http/Requests/FastSalesRequest.php": "6082578dae439a4d8f501323e0242c5f1d2cdca0",
    "resources/js/modules/sales/pages/FastSalesPage.tsx": "5241c774fe5f44787352f5adbd837529f980839c",
    "resources/js/modules/sales/salesTypes.ts": "69359777c6f2f0a4561f7a2577354c5c51856c0c",
}


def git_blob_sha(content: bytes) -> str:
    return sha1(f"blob {len(content)}\0".encode() + content).hexdigest()


def read_guarded(relative_path: str) -> str:
    path = ROOT / relative_path
    content = path.read_bytes()
    actual = git_blob_sha(content)
    expected = EXPECTED_BLOBS[relative_path]
    if actual != expected:
        raise RuntimeError(
            f"Refusing to patch {relative_path}: expected blob {expected}, found {actual}."
        )
    return content.decode()


def write(relative_path: str, content: str) -> None:
    (ROOT / relative_path).write_text(content)


def replace_once(content: str, old: str, new: str, label: str) -> str:
    count = content.count(old)
    if count != 1:
        raise RuntimeError(f"{label}: expected one occurrence, found {count}.")
    return content.replace(old, new, 1)


def regex_once(content: str, pattern: str, replacement: str, label: str) -> str:
    updated, count = re.subn(pattern, replacement, content, count=1, flags=re.S)
    if count != 1:
        raise RuntimeError(f"{label}: expected one regex occurrence, found {count}.")
    return updated


def patch_service() -> None:
    relative = "app/Modules/Sales/Services/FastSalesService.php"
    content = read_guarded(relative)

    content = replace_once(
        content,
        "use Modules\\Payment\\Enums\\PaymentStatus;\n",
        "",
        "remove obsolete PaymentStatus import",
    )
    content = replace_once(
        content,
        "use Modules\\Payment\\Services\\PaymentCreationService;\n",
        "use Modules\\Payment\\Services\\PaymentCreationService;\n"
        "use Modules\\Payment\\Services\\PaymentDocumentLifecycleService;\n"
        "use Modules\\Payment\\Services\\PaymentPostingService;\n",
        "add Payment lifecycle imports",
    )
    content = replace_once(
        content,
        "        private readonly PaymentCreationService $payments,\n"
        "        private readonly FinancePostingInterface $financePostings,\n",
        "        private readonly PaymentCreationService $payments,\n"
        "        private readonly PaymentDocumentLifecycleService $paymentDocuments,\n"
        "        private readonly PaymentPostingService $paymentPostings,\n"
        "        private readonly FinancePostingInterface $financePostings,\n",
        "inject Payment lifecycle services",
    )
    content = replace_once(
        content,
        "            'payment_accounts' => $this->paymentAccountOptions($tenantId, $organizationUnitId, $search, $perPage),\n",
        "",
        "remove Finance account options from Fast Sales context",
    )
    content = replace_once(
        content,
        "                $payment = $this->createCustomerReceipt($resolved, $invoice);\n"
        "                $financePostings = array_merge($financePostings, $this->postPaymentFinance($resolved, $payment));\n",
        "                $payment = $this->createCustomerReceipt($resolved, $invoice);\n",
        "remove Sales-owned receipt posting",
    )

    content = regex_once(
        content,
        r"""    private function createCustomerReceipt\(array \$resolved, Invoice \$invoice\): Payment
    \{.*?
    \}
(?=
    /\*\*
     \* @param  array<string, mixed>  \$resolved
     \* @return list<PostingResultData>
     \*/
    private function postInventoryFinance)""",
        """    private function createCustomerReceipt(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];
        $actorId = $resolved['current_user_id'];
        $data = $this->salesPayments->prepareCustomerReceipt(
            tenantId: (int) $resolved['tenant_id'],
            paymentDate: (string) $resolved['transaction_date'],
            amount: $amount,
            organizationUnitId: $resolved['organization_unit_id'],
            customerId: (int) $resolved['customer']->getKey(),
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            referenceNumber: $payment['reference'] ?? $resolved['customer_reference'],
            lines: $payment['lines'],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: $amount,
                    allocationDate: (string) $resolved['transaction_date'],
                    allowOverpayment: false,
                    metadata: [
                        'fast_sales' => true,
                        'customer_reference' => $resolved['customer_reference'],
                    ],
                ),
            ],
            createdBy: $actorId,
            notes: $resolved['notes'],
        );

        $receipt = $this->payments->create($data);
        $receipt = $this->paymentDocuments->submit($receipt, (int) $receipt->row_version, $actorId);
        $receipt = $this->paymentDocuments->approve($receipt, (int) $receipt->row_version, $actorId);

        return $this->paymentPostings
            ->post($receipt, (int) $receipt->row_version, $actorId)
            ->load(['lines', 'allocations', 'unappliedBalance', 'lifecycleEvents']);
    }
""",
        "replace receipt creation with Payment-owned lifecycle",
    )

    content = regex_once(
        content,
        r"""
    /\*\*
     \* @param  array<string, mixed>  \$resolved
     \* @return list<PostingResultData>
     \*/
    private function postPaymentFinance\(array \$resolved, Payment \$payment\): array
    \{.*?
    \}
(?=
    /\*\*)""",
        "\n",
        "delete Sales-owned receipt journal builder",
    )

    content = regex_once(
        content,
        r"""    private function resolvePayment\(array \$payload, bool \$recordReceipt, array &\$summary, int \$tenantId, \?int \$organizationUnitId, bool \$lockRecords\): array
    \{.*?
    \}
(?=
    /\*\*
     \* @param  list<array<string, mixed>>  \$lines)""",
        """    private function resolvePayment(array $payload, bool $recordReceipt, array &$summary, int $tenantId, ?int $organizationUnitId, bool $lockRecords): array
    {
        if (! $recordReceipt) {
            return ['amount' => '0.000000', 'reference' => null, 'lines' => []];
        }

        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $linePayloads = is_array($payment['lines'] ?? null) && $payment['lines'] !== []
            ? $payment['lines']
            : [[
                'amount' => $payment['amount'] ?? null,
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'reference' => $payment['reference'] ?? null,
                'instrument_number' => $payment['instrument_number'] ?? $payment['cheque_number'] ?? $payment['card_reference'] ?? null,
                'instrument_date' => $payment['instrument_date'] ?? $payment['cheque_date'] ?? null,
                'external_bank_name' => $payment['external_bank_name'] ?? null,
                'external_bank_branch' => $payment['external_bank_branch'] ?? null,
            ]];

        $lines = [];
        $amount = '0.000000';

        foreach ($linePayloads as $line) {
            if (! is_array($line) || ($line['amount'] ?? null) === null) {
                throw new InvalidArgumentException('Receipt amount is required when recording customer receipt.');
            }

            $lineAmount = $this->math->normalize((string) $line['amount']);
            $methodId = $this->nullableInt($line['payment_method_id'] ?? null);
            if ($methodId === null) {
                throw new InvalidArgumentException('Payment method is required when recording customer receipt.');
            }

            $method = $this->paymentMethod($tenantId, $organizationUnitId, $methodId, $lockRecords);
            $reference = $this->nullableString($line['reference'] ?? null);
            $instrumentNumber = $this->nullableString($line['instrument_number'] ?? null);
            $instrumentDate = $this->nullableString($line['instrument_date'] ?? null);
            $externalBankName = $this->nullableString($line['external_bank_name'] ?? null);
            $externalBankBranch = $this->nullableString($line['external_bank_branch'] ?? null);

            if ((bool) $method->requires_reference && $reference === null && $instrumentNumber === null) {
                throw new InvalidArgumentException('Selected receipt method requires a reference.');
            }
            if ((bool) $method->requires_instrument_details
                && ($instrumentNumber === null || $instrumentDate === null || $externalBankName === null)) {
                throw new InvalidArgumentException('Selected receipt method requires instrument number, date, and external bank name.');
            }

            $lines[] = new PaymentLineData(
                amount: $lineAmount,
                paymentMethodId: $methodId,
                referenceNumber: $reference,
                instrumentDirection: 'inbound',
                externalBankName: $externalBankName,
                externalBankBranch: $externalBankBranch,
                instrumentNumber: $instrumentNumber,
                instrumentDate: $instrumentDate,
            );
            $amount = $this->math->add($amount, $lineAmount);
        }

        if ($this->math->compare($amount, $summary['grand_total']) > 0) {
            throw new InvalidArgumentException('Receipt amount cannot exceed customer invoice balance.');
        }

        $summary['received_total'] = $amount;
        $summary['balance_due'] = $this->math->sub($summary['grand_total'], $amount);

        return [
            'amount' => $amount,
            'reference' => $this->nullableString($payment['reference'] ?? null),
            'lines' => $lines,
        ];
    }
""",
        "replace account-coupled payment resolution",
    )

    content = regex_once(
        content,
        r"""
    private function paymentAccount\(int \$tenantId, \?int \$organizationUnitId, int \$accountId, bool \$lockRecords\): FinanceAccount
    \{.*?
    \}
(?=
    private function lockStockBalance)""",
        "\n",
        "remove direct receipt account lookup",
    )

    content = replace_once(
        content,
        "        return ['id' => (int) $payment->getKey(), 'number' => (string) $payment->payment_number, 'status' => $this->enumValue($payment->status), 'url' => '/payments/'.$payment->getKey()];\n",
        "        return ['id' => (int) $payment->getKey(), 'number' => (string) $payment->payment_number, 'status' => $this->enumValue($payment->document_status), 'posting_status' => $this->enumValue($payment->posting_status), 'finance_posting_reference' => $payment->finance_posting_reference, 'url' => '/payments/'.$payment->getKey()];\n",
        "project current Payment lifecycle state",
    )
    content = replace_once(
        content,
        "            ->where(function ($query) use ($tenantId): void {\n"
        "                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);\n"
        "            })\n",
        "            ->where('tenant_id', $tenantId)\n",
        "require tenant-owned payment methods",
    )
    content = replace_once(
        content,
        "            ->get(['id', 'code', 'name', 'method_type', 'requires_reference', 'requires_bank_account'])\n"
        "            ->map(fn (PaymentMethod $method): array => ['id' => (int) $method->getKey(), 'code' => $method->code, 'name' => $method->name, 'method_type' => $this->enumValue($method->method_type), 'requires_reference' => (bool) $method->requires_reference, 'requires_bank_account' => (bool) $method->requires_bank_account])\n",
        "            ->get(['id', 'code', 'name', 'method_type', 'requires_reference', 'requires_instrument_details'])\n"
        "            ->map(fn (PaymentMethod $method): array => ['id' => (int) $method->getKey(), 'code' => $method->code, 'name' => $method->name, 'method_type' => $this->enumValue($method->method_type), 'requires_reference' => (bool) $method->requires_reference, 'requires_instrument_details' => (bool) $method->requires_instrument_details])\n",
        "expose current payment method requirements",
    )
    content = regex_once(
        content,
        r"""
    /\*\*
     \* @return list<array<string, mixed>>
     \*/
    private function paymentAccountOptions\(int \$tenantId, \?int \$organizationUnitId, string \$search, int \$limit\): array
    \{.*?
    \}
(?=
    /\*\*
     \* @return list<array<string, mixed>>
     \*/
    private function taxGroupOptions)""",
        "\n",
        "remove Finance account lookup catalogue",
    )

    forbidden = [
        "PaymentStatus",
        "postPaymentFinance",
        "destination_account_id",
        "payment_accounts",
        "requires_bank_account",
        "internalBankAccountId",
        "header_bank_account_id",
        "destination_accounts",
    ]
    for token in forbidden:
        if token in content:
            raise RuntimeError(f"FastSalesService still contains forbidden token: {token}")

    write(relative, content)


def patch_request() -> None:
    relative = "app/Modules/Sales/Http/Requests/FastSalesRequest.php"
    content = read_guarded(relative)
    content = replace_once(
        content,
        "            'payment.destination_account_id' => ['nullable', 'integer', 'min:1'],\n",
        "",
        "remove destination account validation",
    )
    content = replace_once(
        content,
        "            'payment.lines.*.destination_account_id' => ['nullable', 'integer', 'min:1'],\n",
        "",
        "remove line destination account validation",
    )
    content = replace_once(
        content,
        "            'payment.finance_account_id',\n",
        "            'payment.finance_account_id',\n"
        "            'payment.destination_account_id',\n",
        "prohibit legacy destination account",
    )
    content = replace_once(
        content,
        "            'payment.lines.*.finance_account_id',\n",
        "            'payment.lines.*.finance_account_id',\n"
        "            'payment.lines.*.destination_account_id',\n",
        "prohibit legacy line destination account",
    )
    write(relative, content)


def patch_page() -> None:
    relative = "resources/js/modules/sales/pages/FastSalesPage.tsx"
    content = read_guarded(relative)
    content = replace_once(
        content,
        "    const [paymentAccountId, setPaymentAccountId] = useState('');\n",
        "",
        "remove payment account state",
    )
    content = replace_once(
        content,
        "            setPaymentAccountId('');\n",
        "",
        "remove payment account reset",
    )
    content = replace_once(
        content,
        "        && (!recordReceipt || (paymentAmount.trim() && paymentAccountId)),\n"
        "    ), [customer, customerReference, lines, needsWarehouse, paymentAccountId, paymentAmount, recordReceipt, warehouse]);\n",
        "        && (!recordReceipt || (paymentAmount.trim() && paymentMethodId)),\n"
        "    ), [customer, customerReference, lines, needsWarehouse, paymentAmount, paymentMethodId, recordReceipt, warehouse]);\n",
        "require payment method rather than Finance account",
    )
    content = replace_once(
        content,
        "            destination_account_id: paymentAccountId ? Number(paymentAccountId) : undefined,\n",
        "",
        "remove destination account payload",
    )
    content = regex_once(
        content,
        r'''\n                                <Select
                                    label="Deposit account".*?
                                />''',
        "",
        "remove deposit account control",
    )
    content = replace_once(
        content,
        '<div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">\n'
        '                                <Select\n'
        '                                    label="Payment method"',
        '<div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">\n'
        '                                <Select\n'
        '                                    label="Payment method"',
        "rebalance receipt grid",
    )
    for token in ["paymentAccountId", "Deposit account", "destination_account_id", "payment_accounts"]:
        if token in content:
            raise RuntimeError(f"FastSalesPage still contains forbidden token: {token}")
    write(relative, content)


def patch_types() -> None:
    relative = "resources/js/modules/sales/salesTypes.ts"
    content = read_guarded(relative)
    content = replace_once(
        content,
        "    is_cash_account?: boolean;\n    is_bank_account?: boolean;\n",
        "",
        "remove Finance account presentation flags",
    )
    content = replace_once(
        content,
        "    requires_bank_account?: boolean;\n",
        "    requires_instrument_details?: boolean;\n",
        "rename payment method requirement",
    )
    content = replace_once(
        content,
        "    payment_accounts: FastSalesOptionResource[];\n",
        "",
        "remove payment account context",
    )
    content = replace_once(
        content,
        "        destination_account_id?: number;\n",
        "",
        "remove payment destination account payload",
    )
    content = replace_once(
        content,
        "            destination_account_id?: number;\n",
        "",
        "remove payment line destination account payload",
    )
    content = replace_once(
        content,
        "    status?: string;\n    url: string;\n",
        "    status?: string;\n"
        "    posting_status?: string;\n"
        "    finance_posting_reference?: string | null;\n"
        "    url: string;\n",
        "expose Payment posting projection",
    )
    for token in [
        "payment_accounts",
        "destination_account_id",
        "requires_bank_account",
        "is_cash_account",
        "is_bank_account",
    ]:
        if token in content:
            raise RuntimeError(f"salesTypes still contains forbidden token: {token}")
    write(relative, content)


def add_regression_test() -> None:
    path = ROOT / "app/Modules/Sales/Tests/FastSalesPaymentBoundaryTest.php"
    path.write_text(
        """<?php

declare(strict_types=1);

namespace Modules\\Sales\\Tests;

use PHPUnit\\Framework\\TestCase;

final class FastSalesPaymentBoundaryTest extends TestCase
{
    public function test_receipts_use_the_payment_owned_lifecycle(): void
    {
        $service = file_get_contents(__DIR__.'/../Services/FastSalesService.php');

        self::assertIsString($service);
        self::assertStringContainsString('PaymentDocumentLifecycleService', $service);
        self::assertStringContainsString('PaymentPostingService', $service);
        self::assertStringContainsString('$this->paymentDocuments->submit', $service);
        self::assertStringContainsString('$this->paymentDocuments->approve', $service);
        self::assertStringContainsString('$this->paymentPostings', $service);
        self::assertStringNotContainsString('PaymentStatus', $service);
        self::assertStringNotContainsString('postPaymentFinance', $service);
        self::assertStringNotContainsString('destination_account_id', $service);
        self::assertStringNotContainsString('payment_accounts', $service);
        self::assertStringNotContainsString('requires_bank_account', $service);
    }

    public function test_ui_does_not_request_a_finance_deposit_account(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root.'/resources/js/modules/sales/pages/FastSalesPage.tsx');
        $types = file_get_contents($root.'/resources/js/modules/sales/salesTypes.ts');

        self::assertIsString($page);
        self::assertIsString($types);
        self::assertStringNotContainsString('Deposit account', $page);
        self::assertStringNotContainsString('destination_account_id', $page);
        self::assertStringNotContainsString('payment_accounts', $types);
        self::assertStringNotContainsString('requires_bank_account', $types);
        self::assertStringContainsString('requires_instrument_details', $types);
    }
}
"""
    )


def add_change_record() -> None:
    path = ROOT / "docs/changes/2026-06-29-fast-sales-payment-lifecycle.md"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        """# Fast Sales Payment Lifecycle

Fast Sales customer receipts now use the Payment module as the authoritative owner of receipt creation, document approval, Finance posting, and invoice-allocation realization.

## Changed

- Removed client-selected Finance deposit accounts from Fast Sales context, request contracts, and UI.
- Explicitly reject legacy `destination_account_id` fields instead of silently accepting them.
- Create receipt documents as Payment drafts, then submit, approve, and post through Payment-owned lifecycle services.
- Removed direct receipt journal construction from Sales.
- Updated payment-method lookups to expose `requires_instrument_details`.
- Added regression coverage preventing Fast Sales from reintroducing direct Finance-account selection or obsolete Payment status usage.

## Verification

- Hash-guarded source transformation against the audited branch blobs.
- PHP syntax checks for changed Sales files.
- TypeScript semantic check and focused ESLint verification.
- Static Payment-boundary regression assertions and `git diff --check`.
"""
    )


def main() -> None:
    patch_service()
    patch_request()
    patch_page()
    patch_types()
    add_regression_test()
    add_change_record()


if __name__ == "__main__":
    main()
