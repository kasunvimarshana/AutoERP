<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use InvalidArgumentException;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCalculationService;
use Modules\Invoice\Validators\InvoiceValidationService;
use Tests\TestCase;

final class InvoiceCalculationServiceTest extends TestCase
{
    public function test_it_preserves_inclusive_tax_line_totals_and_applies_withholding_once(): void
    {
        $data = new CreateInvoiceData(
            tenantId: 100,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-29',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Tax-inclusive service',
                    quantity: '1.000000',
                    unitPrice: '100.000000',
                    taxAmount: '15.000000',
                    lineTotal: '100.000000',
                    metadata: [
                        InvoiceTaxMetadata::TAXES => [[
                            InvoiceTaxMetadata::CALCULATION_METHOD => InvoiceTaxMetadata::CALCULATION_METHOD_INCLUSIVE,
                            InvoiceTaxMetadata::TAX_AMOUNT => '15.000000',
                            InvoiceTaxMetadata::IS_WITHHOLDING => false,
                        ]],
                    ],
                ),
            ],
            adjustments: [
                new InvoiceAdjustmentData(
                    name: 'Tax withholding',
                    adjustmentType: AdjustmentType::Withholding,
                    effect: AdjustmentEffect::Decrease,
                    amount: '5.000000',
                ),
            ],
        );

        app(InvoiceValidationService::class)->validateForCreation($data);
        $result = app(InvoiceCalculationService::class)->calculate($data);

        $this->assertSame('100.000000', $result->subtotal);
        $this->assertSame('15.000000', $result->taxTotal);
        $this->assertSame('5.000000', $result->discountTotal);
        $this->assertSame('95.000000', $result->grandTotal);
    }

    public function test_it_rejects_adjustment_types_with_the_wrong_effect(): void
    {
        $data = new CreateInvoiceData(
            tenantId: 100,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-29',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Service',
                    quantity: '1.000000',
                    unitPrice: '100.000000',
                ),
            ],
            adjustments: [
                new InvoiceAdjustmentData(
                    name: 'Invalid discount',
                    adjustmentType: AdjustmentType::Discount,
                    effect: AdjustmentEffect::Increase,
                    amount: '5.000000',
                ),
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice adjustment type [discount] requires effect [decrease].');

        app(InvoiceValidationService::class)->validateForCreation($data);
    }
}
