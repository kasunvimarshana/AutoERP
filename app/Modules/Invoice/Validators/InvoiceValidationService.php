<?php

declare(strict_types=1);

namespace Modules\Invoice\Validators;

use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Services\DecimalMath;

final class InvoiceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateForCreation(CreateInvoiceData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Invoice tenant is required.');
        }

        if ($data->organizationUnitId !== null && $data->organizationUnitId < 1) {
            throw new InvalidArgumentException('Invoice organization unit must be a positive id.');
        }

        if (trim($data->invoiceDate) === '') {
            throw new InvalidArgumentException('Invoice date is required.');
        }

        if ($this->math->isNegative($data->exchangeRate) || $this->math->isZero($data->exchangeRate)) {
            throw new InvalidArgumentException('Invoice exchange rate must be greater than zero.');
        }

        foreach ($data->lines as $line) {
            if (! $line instanceof InvoiceLineData) {
                throw new InvalidArgumentException('Invoice lines must be InvoiceLineData instances.');
            }
            $this->validateLine($line);
        }

        foreach ($data->sources as $source) {
            if (! $source instanceof InvoiceSourceData) {
                throw new InvalidArgumentException('Invoice sources must be InvoiceSourceData instances.');
            }
            $this->validateTenantScope($data->tenantId, $data->organizationUnitId, $source->tenantId, $source->organizationUnitId);
            $this->assertNonNegative($source->sourceSubtotal, 'Source subtotal');
            $this->assertNonNegative($source->sourceGrandTotal, 'Source grand total');
            $this->assertNonNegative($source->invoicedAmount, 'Source invoiced amount');
        }

        foreach ($data->sourceLines as $sourceLine) {
            if (! $sourceLine instanceof InvoiceSourceLineData) {
                throw new InvalidArgumentException('Invoice source lines must be InvoiceSourceLineData instances.');
            }
            $this->validateTenantScope($data->tenantId, $data->organizationUnitId, $sourceLine->tenantId, $sourceLine->organizationUnitId);
            $this->assertNonNegative($sourceLine->sourceQuantity, 'Source quantity');
            $this->assertNonNegative($sourceLine->previouslyInvoicedQuantity, 'Previously invoiced quantity');
            $this->assertNonNegative($sourceLine->invoicedQuantity, 'Invoiced quantity');
            $this->assertNonNegative($sourceLine->sourceLineTotal, 'Source line total');
        }

        foreach ($data->adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustmentData) {
                throw new InvalidArgumentException('Invoice adjustments must be InvoiceAdjustmentData instances.');
            }
            $this->validateAdjustment($adjustment);
        }
    }

    private function validateLine(InvoiceLineData $line): void
    {
        if ($line->lineNumber < 1) {
            throw new InvalidArgumentException('Invoice line number must be positive.');
        }

        if (trim($line->description) === '') {
            throw new InvalidArgumentException('Invoice line description is required.');
        }

        $this->assertNonNegative($line->quantity, 'Invoice line quantity');
        $this->assertNonNegative($line->unitPrice, 'Invoice line unit price');
        $this->assertNonNegative($line->discountAmount, 'Invoice line discount amount');
        $this->assertNonNegative($line->taxAmount, 'Invoice line tax amount');
        $this->assertNonNegative($line->chargeAmount, 'Invoice line charge amount');

        if ($line->lineTotal !== null) {
            $this->assertNonNegative($line->lineTotal, 'Invoice line total');
        }
    }

    private function validateAdjustment(InvoiceAdjustmentData $adjustment): void
    {
        if (trim($adjustment->name) === '') {
            throw new InvalidArgumentException('Invoice adjustment name is required.');
        }

        if (! in_array($adjustment->calculationType, ['fixed', 'percentage'], true)) {
            throw new InvalidArgumentException('Invoice adjustment calculation type must be fixed or percentage.');
        }

        $this->assertNonNegative($adjustment->rate, 'Invoice adjustment rate');
        $this->assertNonNegative($adjustment->amount, 'Invoice adjustment amount');
        if ($adjustment->sourceAmount !== null) {
            $this->assertNonNegative($adjustment->sourceAmount, 'Invoice adjustment source amount');
        }

        if (($adjustment->sourceAdjustmentType === null) !== ($adjustment->sourceAdjustmentId === null)) {
            throw new InvalidArgumentException('Source adjustment type and id must be provided together.');
        }
    }

    private function validateTenantScope(
        int $invoiceTenantId,
        ?int $invoiceOrganizationUnitId,
        int $sourceTenantId,
        ?int $sourceOrganizationUnitId,
    ): void {
        if ($invoiceTenantId !== $sourceTenantId) {
            throw new InvalidArgumentException('Invoice source tenant must match invoice tenant.');
        }

        if ($invoiceOrganizationUnitId !== $sourceOrganizationUnitId) {
            throw new InvalidArgumentException('Invoice source organization unit must match invoice organization unit.');
        }
    }

    private function assertNonNegative(string $value, string $label): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }
    }
}
