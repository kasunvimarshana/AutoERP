<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\InvoicePostingLineData;
use Modules\Invoice\DTOs\InvoicePostingPlanData;

final class InvoicePostingPlanFactory
{
    private const ZERO = '0.000000';

    public function __construct(private readonly DecimalMath $math) {}

    public function outbound(
        FinancePostingProfileCode $profile,
        string $postingDate,
        FinanceAccountRoleCode $revenueRole,
        string $revenueAmount,
        string $taxAmount = self::ZERO,
        string $withholdingAmount = self::ZERO,
        ?string $description = null,
    ): InvoicePostingPlanData {
        $revenueAmount = $this->math->normalize($revenueAmount);
        $taxAmount = $this->math->normalize($taxAmount);
        $withholdingAmount = $this->math->normalize($withholdingAmount);
        $receivableAmount = $this->math->sub(
            $this->math->add($revenueAmount, $taxAmount),
            $withholdingAmount,
        );

        $lines = [];
        $this->appendDebit($lines, FinanceAccountRoleCode::Receivable, $receivableAmount, 'Customer receivable');
        $this->appendDebit($lines, FinanceAccountRoleCode::WithholdingReceivable, $withholdingAmount, 'Withholding receivable');
        $this->appendCredit($lines, $revenueRole, $revenueAmount, $description ?? 'Invoice revenue');
        $this->appendCredit($lines, FinanceAccountRoleCode::TaxPayable, $taxAmount, 'Output tax payable');

        return new InvoicePostingPlanData(
            profile: $profile,
            postingDate: $postingDate,
            lines: $lines,
            description: $description,
        );
    }

    public function inbound(
        FinancePostingProfileCode $profile,
        string $postingDate,
        FinanceAccountRoleCode $expenseRole,
        string $expenseAmount,
        string $taxAmount = self::ZERO,
        string $withholdingAmount = self::ZERO,
        ?string $description = null,
    ): InvoicePostingPlanData {
        $expenseAmount = $this->math->normalize($expenseAmount);
        $taxAmount = $this->math->normalize($taxAmount);
        $withholdingAmount = $this->math->normalize($withholdingAmount);
        $payableAmount = $this->math->sub(
            $this->math->add($expenseAmount, $taxAmount),
            $withholdingAmount,
        );

        $lines = [];
        $this->appendDebit($lines, $expenseRole, $expenseAmount, $description ?? 'Invoice expense');
        $this->appendDebit($lines, FinanceAccountRoleCode::TaxReceivable, $taxAmount, 'Input tax receivable');
        $this->appendCredit($lines, FinanceAccountRoleCode::Payable, $payableAmount, 'Supplier payable');
        $this->appendCredit($lines, FinanceAccountRoleCode::WithholdingPayable, $withholdingAmount, 'Withholding payable');

        return new InvoicePostingPlanData(
            profile: $profile,
            postingDate: $postingDate,
            lines: $lines,
            description: $description,
        );
    }

    /** @param list<InvoicePostingLineData> $lines */
    private function appendDebit(
        array &$lines,
        FinanceAccountRoleCode $role,
        string $amount,
        string $description,
    ): void {
        if ($this->math->isZero($amount)) {
            return;
        }

        $lines[] = new InvoicePostingLineData(
            role: $role,
            debit: $amount,
            description: $description,
        );
    }

    /** @param list<InvoicePostingLineData> $lines */
    private function appendCredit(
        array &$lines,
        FinanceAccountRoleCode $role,
        string $amount,
        string $description,
    ): void {
        if ($this->math->isZero($amount)) {
            return;
        }

        $lines[] = new InvoicePostingLineData(
            role: $role,
            credit: $amount,
            description: $description,
        );
    }
}
