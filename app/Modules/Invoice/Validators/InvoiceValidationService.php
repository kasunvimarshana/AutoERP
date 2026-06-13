<?php

declare(strict_types=1);

namespace Modules\Invoice\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;

final class InvoiceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateForCreation(CreateInvoiceData $data): void
    {
        $this->validateHeader($data);
        $invoiceLineSources = $this->validateLines($data);
        $sourceKeys = $this->validateSources($data);
        $sourceLineKeys = $this->validateSourceLines($data, $sourceKeys);
        $this->validateInvoiceLineSources($invoiceLineSources, $sourceLineKeys);
        $this->validateAdjustments($data);

        if ($data->lines === []) {
            throw new InvalidArgumentException('Invoice requires at least one line.');
        }
    }

    private function validateHeader(CreateInvoiceData $data): void
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

        $this->assertPositive($data->exchangeRate, 'Invoice exchange rate');
    }

    /**
     * @return array<string, true>
     */
    private function validateLines(CreateInvoiceData $data): array
    {
        $lineNumbers = [];
        $invoiceLineSources = [];
        foreach ($data->lines as $line) {
            if (! $line instanceof InvoiceLineData) {
                throw new InvalidArgumentException('Invoice lines must be InvoiceLineData instances.');
            }
            $this->validateLine($line);

            if (isset($lineNumbers[$line->lineNumber])) {
                throw new InvalidArgumentException('Invoice line numbers must be unique.');
            }
            $lineNumbers[$line->lineNumber] = true;

            if (($line->sourceLineType === null) !== ($line->sourceLineId === null)) {
                throw new InvalidArgumentException('Invoice line source type and id must be provided together.');
            }
            if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
                $sourceLineKey = $this->key($line->sourceLineType, $line->sourceLineId);
                if (isset($invoiceLineSources[$sourceLineKey])) {
                    throw new InvalidArgumentException(
                        'An invoice source line cannot be referenced by more than one invoice line.',
                    );
                }
                $invoiceLineSources[$sourceLineKey] = true;
            }
        }

        return $invoiceLineSources;
    }

    /**
     * @return array<string, true>
     */
    private function validateSources(CreateInvoiceData $data): array
    {
        $sourceKeys = [];
        foreach ($data->sources as $source) {
            if (! $source instanceof InvoiceSourceData) {
                throw new InvalidArgumentException('Invoice sources must be InvoiceSourceData instances.');
            }
            $this->validateTenantScope($data->tenantId, $data->organizationUnitId, $source->tenantId, $source->organizationUnitId);
            $this->assertReference($source->sourceType, $source->sourceId, 'Invoice source');
            $this->assertNonNegative($source->sourceSubtotal, 'Source subtotal');
            $this->assertNonNegative($source->sourceGrandTotal, 'Source grand total');
            $this->assertNonNegative($source->invoicedAmount, 'Source invoiced amount');

            $sourceKey = $this->key($source->sourceType, $source->sourceId);
            if (isset($sourceKeys[$sourceKey])) {
                throw new InvalidArgumentException('Invoice sources must be unique.');
            }
            $sourceKeys[$sourceKey] = true;
        }

        return $sourceKeys;
    }

    /**
     * @param  array<string, true>  $sourceKeys
     * @return array<string, true>
     */
    private function validateSourceLines(CreateInvoiceData $data, array $sourceKeys): array
    {
        $sourceLineKeys = [];
        foreach ($data->sourceLines as $sourceLine) {
            if (! $sourceLine instanceof InvoiceSourceLineData) {
                throw new InvalidArgumentException('Invoice source lines must be InvoiceSourceLineData instances.');
            }
            $this->validateTenantScope($data->tenantId, $data->organizationUnitId, $sourceLine->tenantId, $sourceLine->organizationUnitId);
            $this->assertReference($sourceLine->sourceType, $sourceLine->sourceId, 'Invoice source line source');
            $this->assertReference($sourceLine->sourceLineType, $sourceLine->sourceLineId, 'Invoice source line');
            $this->assertPositive($sourceLine->sourceQuantity, 'Source quantity');
            $this->assertNonNegative($sourceLine->previouslyInvoicedQuantity, 'Previously invoiced quantity');
            $this->assertPositive($sourceLine->invoicedQuantity, 'Invoiced quantity');
            $this->assertNonNegative($sourceLine->sourceUnitPrice, 'Source unit price');
            $this->assertNonNegative($sourceLine->sourceLineTotal, 'Source line total');
            if ($sourceLine->invoicedLineTotal !== null) {
                $this->assertNonNegative($sourceLine->invoicedLineTotal, 'Invoiced line total');
            }

            if (! isset($sourceKeys[$this->key($sourceLine->sourceType, $sourceLine->sourceId)])) {
                throw new InvalidArgumentException('Invoice source line must reference an invoice source.');
            }

            $sourceLineKey = $this->key($sourceLine->sourceLineType, $sourceLine->sourceLineId);
            if (isset($sourceLineKeys[$sourceLineKey])) {
                throw new InvalidArgumentException('Invoice source lines must be unique.');
            }
            $sourceLineKeys[$sourceLineKey] = true;
        }

        return $sourceLineKeys;
    }

    /**
     * @param  array<string, true>  $invoiceLineSources
     * @param  array<string, true>  $sourceLineKeys
     */
    private function validateInvoiceLineSources(
        array $invoiceLineSources,
        array $sourceLineKeys,
    ): void {
        foreach (array_keys($invoiceLineSources) as $sourceLineKey) {
            if (! isset($sourceLineKeys[$sourceLineKey])) {
                throw new InvalidArgumentException(
                    'Invoice line source must reference an invoice source line.',
                );
            }
        }
    }

    private function validateAdjustments(CreateInvoiceData $data): void
    {
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

        $this->assertPositive($line->quantity, 'Invoice line quantity');
        $this->assertNonNegative($line->unitPrice, 'Invoice line unit price');
        $this->assertNonNegative($line->discountAmount, 'Invoice line discount amount');
        $this->assertNonNegative($line->taxAmount, 'Invoice line tax amount');
        $this->assertNonNegative($line->chargeAmount, 'Invoice line charge amount');

        if ($line->lineTotal !== null) {
            $this->assertNonNegative($line->lineTotal, 'Invoice line total');
            if ($this->math->compare($line->lineTotal, $this->expectedLineTotal($line)) !== 0) {
                throw new InvalidArgumentException(
                    'Invoice line total must match its quantity, price, discount, tax, and charge.',
                );
            }
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

        if (($adjustment->sourceType === null) !== ($adjustment->sourceId === null)) {
            throw new InvalidArgumentException('Adjustment source type and id must be provided together.');
        }

        if ($adjustment->sourceAdjustmentType !== null && $adjustment->sourceAdjustmentId !== null) {
            $this->assertReference(
                $adjustment->sourceAdjustmentType,
                $adjustment->sourceAdjustmentId,
                'Source adjustment',
            );
        }

        if ($adjustment->sourceType !== null && $adjustment->sourceId !== null) {
            $this->assertReference($adjustment->sourceType, $adjustment->sourceId, 'Adjustment source');
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

    private function assertPositive(string $value, string $label): void
    {
        if ($this->math->isNegative($value) || $this->math->isZero($value)) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
    }

    private function assertReference(string $type, int $id, string $label): void
    {
        if (trim($type) === '' || $id < 1) {
            throw new InvalidArgumentException($label.' type and id are required.');
        }
    }

    private function expectedLineTotal(InvoiceLineData $line): string
    {
        $total = $this->math->mul($line->quantity, $line->unitPrice);
        $total = $this->math->sub($total, $line->discountAmount);
        $total = $this->math->add($total, $line->taxAmount);

        return $this->math->add($total, $line->chargeAmount);
    }

    private function key(string $type, int $id): string
    {
        return $type.':'.$id;
    }
}
