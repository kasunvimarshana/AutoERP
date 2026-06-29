<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;

trait ResolvesFastSales
{
    /** @param array<string, mixed> $payload */
    private function resolve(array $payload, bool $lockRecords): array
    {
        $tenantId = (int) $payload['tenant_id'];
        $organizationUnitId = $this->nullableInt($payload['organization_unit_id'] ?? null);
        $transactionDate = (string) $payload['transaction_date'];
        $customer = $this->customer($tenantId, $organizationUnitId, (int) $payload['customer_id'], $lockRecords);
        $customerReference = trim((string) ($payload['customer_reference'] ?? ''));
        $currencyId = $this->currencyId($payload, $customer, $tenantId, $organizationUnitId, $lockRecords);
        $exchangeRate = $this->math->normalize((string) ($payload['exchange_rate'] ?? '1.000000'));
        $warehouseId = $this->nullableInt($payload['warehouse_id'] ?? null);
        $warehouseLocationId = $this->nullableInt($payload['warehouse_location_id'] ?? null);
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        $createOrderOnly = (bool) ($options['create_sales_order_only'] ?? false);
        $deliverItemsNow = (bool) ($options['deliver_items_now'] ?? false);
        $createInvoice = (bool) ($options['create_customer_invoice_now'] ?? false);
        $recordReceipt = (bool) ($options['record_customer_receipt_now'] ?? false);

        if (($deliverItemsNow || $createOrderOnly) && $warehouseId === null) {
            throw new InvalidArgumentException('Warehouse is required for stock sales workflows.');
        }
        if ($warehouseId !== null) {
            $this->warehouse($tenantId, $organizationUnitId, $warehouseId, $lockRecords);
            if ($warehouseLocationId !== null) {
                $this->warehouseLocation($tenantId, $organizationUnitId, $warehouseId, $warehouseLocationId, $lockRecords);
            }
        }

        $lines = $this->resolveLines(
            is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
            $customer,
            $tenantId,
            $organizationUnitId,
            $transactionDate,
            $currencyId,
            $warehouseId,
            $warehouseLocationId,
            $deliverItemsNow,
            $lockRecords,
        );
        $this->validateMode($lines, $createOrderOnly, $deliverItemsNow, $createInvoice, $recordReceipt);
        $summary = $this->summary($lines);
        $payment = $this->resolvePayment($payload, $recordReceipt, $summary, $tenantId, $organizationUnitId, $lockRecords);
        $this->validateCredit($customer, $summary, $payment['amount']);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'current_user_id' => $this->nullableInt($payload['current_user_id'] ?? null),
            'customer' => $customer,
            'customer_reference' => $customerReference,
            'transaction_date' => $transactionDate,
            'due_date' => $this->dueDate($transactionDate, (string) ($payload['payment_terms'] ?? ''), $payload['due_date'] ?? null),
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $warehouseLocationId,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'payment_terms' => trim((string) ($payload['payment_terms'] ?? '')),
            'notes' => $this->notes($payload),
            'options' => [
                'create_sales_order_only' => $createOrderOnly,
                'deliver_items_now' => $deliverItemsNow,
                'create_customer_invoice_now' => $createInvoice,
                'record_customer_receipt_now' => $recordReceipt,
            ],
            'mode' => $this->mode($lines, $createOrderOnly, $deliverItemsNow, $createInvoice, $recordReceipt),
            'lines' => $lines,
            'summary' => $summary,
            'payment' => $payment,
        ];
    }

    /** @param list<array<string, mixed>> $linePayloads */
    private function resolveLines(array $linePayloads, Customer $customer, int $tenantId, ?int $organizationUnitId, string $transactionDate, ?int $currencyId, ?int $warehouseId, ?int $warehouseLocationId, bool $deliverItemsNow, bool $lockRecords): array
    {
        if ($linePayloads === []) {
            throw new InvalidArgumentException('Fast sales requires at least one line.');
        }
        $resolved = [];
        $taxLines = [];
        foreach (array_values($linePayloads) as $index => $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Fast sales lines are invalid.');
            }
            $item = $this->item($tenantId, $organizationUnitId, (int) $line['item_id'], $lockRecords);
            $this->assertSalesUsage($item, $organizationUnitId);
            $variantId = $this->nullableInt($line['item_variant_id'] ?? null);
            if ($variantId !== null) {
                $this->validator->itemVariant($tenantId, $organizationUnitId, (int) $item->getKey(), $variantId);
            }
            $quantity = $this->math->normalize((string) $line['quantity']);
            $uomId = $this->resolveUomId($tenantId, $organizationUnitId, $item, $line);
            $uomBasis = $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, $quantity);
            $uom = $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, '1.000000');
            $priceResolution = $this->priceResolver->resolvePrice(
                item: $item,
                context: $this->priceContext($item),
                uomId: $uomId,
                organizationUnitId: $organizationUnitId,
                currencyId: $currencyId,
                date: $transactionDate,
                variantId: $variantId,
            );
            $unitPrice = $priceResolution->amount ?? '0.000000';
            if ($this->math->compare($unitPrice, '0.000000') <= 0) {
                throw new InvalidArgumentException('Sales unit price must be configured and greater than zero.');
            }
            if (array_key_exists('unit_price', $line) && $line['unit_price'] !== null) {
                $requestedPrice = $this->math->normalize((string) $line['unit_price']);
                if ($this->math->compare($requestedPrice, $unitPrice) !== 0) {
                    throw new InvalidArgumentException('Manual price overrides are not permitted in fast sales.');
                }
            }
            $discount = $this->math->normalize((string) ($line['discount_amount'] ?? '0.000000'));
            $taxGroupId = $this->nullableInt($line['tax_group_id'] ?? null) ?? $this->defaultTaxGroupId($item);
            if ($taxGroupId !== null) {
                $this->taxGroup($tenantId, $organizationUnitId, $taxGroupId, $lockRecords);
            }
            $isStock = $this->isStockItem($item);
            $lineSubtotal = $this->math->mul($quantity, $unitPrice);
            if ($this->math->compare($discount, $lineSubtotal) > 0) {
                throw new InvalidArgumentException('Sales line discount cannot exceed line subtotal.');
            }
            $availableBaseQuantity = null;
            $availableQuantity = null;
            if ($isStock && $warehouseId !== null) {
                $this->lockStockBalance($tenantId, $organizationUnitId, (int) $item->getKey(), $warehouseId, $variantId, $warehouseLocationId, $lockRecords);
                $availability = $this->stockAvailability->availability(new StockBalanceData(
                    tenantId: $tenantId,
                    itemId: (int) $item->getKey(),
                    warehouseId: $warehouseId,
                    organizationUnitId: $organizationUnitId,
                    itemVariantId: $variantId,
                    warehouseLocationId: $warehouseLocationId,
                ));
                $availableBaseQuantity = $this->math->normalize($availability->quantityAvailable);
                $availableQuantity = $this->math->div($availableBaseQuantity, $uomBasis['factor'], 6);
                if ($deliverItemsNow && $this->math->compare($uomBasis['base_quantity'], $availableBaseQuantity) > 0) {
                    throw new InvalidArgumentException('Insufficient stock is available for delivery.');
                }
            }
            $resolved[] = [
                'line_number' => $index + 1,
                'item' => $item,
                'item_id' => (int) $item->getKey(),
                'item_variant_id' => $variantId,
                'description' => trim((string) ($line['description'] ?? '')) !== '' ? trim((string) $line['description']) : (string) $item->name,
                'uom_id' => $uomId,
                'uom' => $uom['ordered_uom_id'] ?? null,
                'uom_model' => $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, '1.000000'),
                'quantity' => $quantity,
                'base_uom_id' => $uomBasis['base_uom_id'],
                'uom_conversion_factor' => $uomBasis['factor'],
                'base_quantity' => $uomBasis['base_quantity'],
                'unit_price' => $unitPrice,
                'price_resolution' => [
                    'source' => $priceResolution->source,
                    'price_type' => $priceResolution->priceType,
                    'price_id' => $priceResolution->priceId,
                    'currency_id' => $priceResolution->currencyId,
                    'uom_id' => $priceResolution->uomId,
                    'metadata' => $priceResolution->metadata,
                ],
                'discount_amount' => $discount,
                'tax_group_id' => $taxGroupId,
                'is_stock' => $isStock,
                'line_type' => $this->invoiceLineType($item),
                'line_subtotal' => $lineSubtotal,
                'available_base_quantity' => $availableBaseQuantity,
                'available_quantity' => $availableQuantity,
            ];
            $taxLines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: $quantity,
                unitPrice: $unitPrice,
                itemId: (int) $item->getKey(),
                taxGroupId: $taxGroupId,
                discountBeforeTax: $discount,
            );
        }
        $taxResult = $this->taxes->calculate(new TaxCalculationData(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: $transactionDate,
            organizationUnitId: $organizationUnitId,
            customerId: (int) $customer->getKey(),
            lines: $taxLines,
        ));
        foreach ($taxResult->lineResults as $result) {
            $index = $result->lineNumber - 1;
            $withholding = $this->math->normalize($result->withholdingAmount);
            $nonWithholdingTax = $this->math->sub((string) $result->taxAmount, $withholding);
            $resolved[$index]['tax_amount'] = $this->math->normalize((string) $result->taxAmount);
            $resolved[$index]['non_withholding_tax_amount'] = $nonWithholdingTax;
            $resolved[$index]['withholding_amount'] = $withholding;
            $resolved[$index]['line_total'] = $this->math->normalize((string) $result->totalAmount);
            $resolved[$index]['taxes'] = array_map(static fn ($tax): array => [
                'tax_id' => $tax->taxId,
                'tax_code' => $tax->taxCode,
                'tax_name' => $tax->taxName,
                'rate' => $tax->rate,
                'tax_amount' => $tax->taxAmount,
                'is_withholding' => $tax->isWithholding,
            ], $result->taxes);
        }
        return $resolved;
    }

    /** @param list<array<string, mixed>> $lines */
    private function summary(array $lines): array
    {
        $summary = [
            'subtotal' => '0.000000',
            'discount_total' => '0.000000',
            'tax_total' => '0.000000',
            'withholding_total' => '0.000000',
            'grand_total' => '0.000000',
            'received_total' => '0.000000',
            'balance_due' => '0.000000',
            'revenue_total' => '0.000000',
            'stock_revenue_total' => '0.000000',
            'non_stock_revenue_total' => '0.000000',
        ];
        foreach ($lines as $line) {
            $netRevenue = $this->math->sub($line['line_subtotal'], $line['discount_amount']);
            $summary['subtotal'] = $this->math->add($summary['subtotal'], $line['line_subtotal']);
            $summary['discount_total'] = $this->math->add($summary['discount_total'], $line['discount_amount']);
            $summary['tax_total'] = $this->math->add($summary['tax_total'], $line['non_withholding_tax_amount']);
            $summary['withholding_total'] = $this->math->add($summary['withholding_total'], $line['withholding_amount']);
            $summary['grand_total'] = $this->math->add($summary['grand_total'], $line['line_total']);
            $summary['revenue_total'] = $this->math->add($summary['revenue_total'], $netRevenue);
            if ((bool) $line['is_stock']) {
                $summary['stock_revenue_total'] = $this->math->add($summary['stock_revenue_total'], $netRevenue);
            } else {
                $summary['non_stock_revenue_total'] = $this->math->add($summary['non_stock_revenue_total'], $netRevenue);
            }
        }
        $summary['balance_due'] = $summary['grand_total'];
        return $summary;
    }

    /** @param array<string, mixed> $payload @param array<string, string> $summary */
    private function resolvePayment(array $payload, bool $recordReceipt, array &$summary, int $tenantId, ?int $organizationUnitId, bool $lockRecords): array
    {
        if (! $recordReceipt) {
            return ['amount' => '0.000000', 'reference' => null, 'lines' => []];
        }
        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $linePayloads = is_array($payment['lines'] ?? null) && $payment['lines'] !== [] ? $payment['lines'] : [[
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
            if ((bool) $method->requires_instrument_details && ($instrumentNumber === null || $instrumentDate === null || $externalBankName === null)) {
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
        return ['amount' => $amount, 'reference' => $this->nullableString($payment['reference'] ?? null), 'lines' => $lines];
    }
}
