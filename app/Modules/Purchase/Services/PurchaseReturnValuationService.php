<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Purchase\DTOs\PurchaseReturnLineValuationData;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseReturnValuationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function previewFromReceiptLine(
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        ?int $currentReturnId = null,
    ): PurchaseReturnLineValuationData
    {
        return $this->calculateFromReceiptLine(
            $sourceLine,
            $returnedQuantity,
            $currentReturnId,
            null,
            allowReadQueries: true,
        );
    }

    /**
     * @param  array{base_amount: string, discount_amount: string, tax_amount: string, charge_amount: string, line_total: string}  $postedLineSums
     */
    public function fromLockedReceiptLine(
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        int $currentReturnId,
        array $postedLineSums,
    ): PurchaseReturnLineValuationData
    {
        return $this->calculateFromReceiptLine(
            $sourceLine,
            $returnedQuantity,
            $currentReturnId,
            $postedLineSums,
            allowReadQueries: false,
        );
    }

    public function fromReceiptLine(
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        ?int $currentReturnId = null,
    ): PurchaseReturnLineValuationData
    {
        return $this->previewFromReceiptLine($sourceLine, $returnedQuantity, $currentReturnId);
    }

    /**
     * @param  array{base_amount: string, discount_amount: string, tax_amount: string, charge_amount: string, line_total: string}|null  $postedLineSums
     */
    private function calculateFromReceiptLine(
        GoodsReceiptNoteLine $sourceLine,
        string $returnedQuantity,
        ?int $currentReturnId,
        ?array $postedLineSums,
        bool $allowReadQueries,
    ): PurchaseReturnLineValuationData
    {
        $sourceQuantity = $this->math->normalize((string) $sourceLine->accepted_quantity);
        $returnedQuantity = $this->math->normalize($returnedQuantity);
        $previouslyReturned = $this->math->normalize((string) $sourceLine->returned_quantity);
        $remainingQuantity = $this->math->sub($sourceQuantity, $this->math->add($previouslyReturned, $returnedQuantity));
        if ($this->math->isNegative($remainingQuantity)) {
            throw new InvalidArgumentException('Purchase return quantity cannot exceed source receipt returnable quantity.');
        }

        $isFinalReturn = $this->math->isZero($remainingQuantity);
        $ratio = $this->math->isZero($sourceQuantity)
            ? '0.000000000000'
            : $this->math->div($returnedQuantity, $sourceQuantity, 12);

        if ($isFinalReturn) {
            if ($postedLineSums === null && ! $allowReadQueries) {
                throw new InvalidArgumentException('Locked purchase return valuation requires posted return line sums.');
            }
            $posted = $postedLineSums ?? $this->postedReturnLineSums((int) $sourceLine->getKey(), $currentReturnId);
            $baseAmount = $this->residual((string) $sourceLine->line_subtotal, $posted['base_amount'], 'base amount');
            $discountAmount = $this->residual((string) $sourceLine->discount_amount, $posted['discount_amount'], 'discount amount');
            $taxAmount = $this->residual((string) $sourceLine->tax_amount, $posted['tax_amount'], 'tax amount');
            $chargeAmount = $this->residual((string) $sourceLine->charge_amount, $posted['charge_amount'], 'charge amount');
            $lineTotal = $this->residual((string) $sourceLine->line_total, $posted['line_total'], 'line total');
        } else {
            $baseAmount = $this->math->mul((string) $sourceLine->line_subtotal, $ratio);
            $discountAmount = $this->math->mul((string) $sourceLine->discount_amount, $ratio);
            $taxAmount = $this->math->mul((string) $sourceLine->tax_amount, $ratio);
            $chargeAmount = $this->math->mul((string) $sourceLine->charge_amount, $ratio);
            $lineTotal = $this->math->add(
                $this->math->sub($baseAmount, $discountAmount),
                $this->math->add($taxAmount, $chargeAmount),
            );
        }

        $costBasis = $this->costBasis($sourceLine, $allowReadQueries);

        return new PurchaseReturnLineValuationData(
            sourceQuantity: $sourceQuantity,
            previouslyReturnedQuantity: $previouslyReturned,
            remainingQuantity: $remainingQuantity,
            unitPrice: $this->math->normalize((string) $sourceLine->unit_price),
            costBasis: $costBasis,
            baseAmount: $baseAmount,
            discountAmount: $discountAmount,
            taxAmount: $taxAmount,
            chargeAmount: $chargeAmount,
            lineTotal: $lineTotal,
        );
    }

    public function manual(string $quantity, string $costBasis): PurchaseReturnLineValuationData
    {
        $quantity = $this->math->normalize($quantity);
        $costBasis = $this->math->normalize($costBasis);
        $lineTotal = $this->math->mul($quantity, $costBasis);

        return new PurchaseReturnLineValuationData(
            sourceQuantity: $quantity,
            previouslyReturnedQuantity: '0.000000',
            remainingQuantity: '0.000000',
            unitPrice: $costBasis,
            costBasis: $costBasis,
            baseAmount: $lineTotal,
            discountAmount: '0.000000',
            taxAmount: '0.000000',
            chargeAmount: '0.000000',
            lineTotal: $lineTotal,
        );
    }

    /**
     * @return array{base_amount: string, discount_amount: string, tax_amount: string, charge_amount: string, line_total: string}
     */
    private function postedReturnLineSums(int $sourceLineId, ?int $currentReturnId): array
    {
        $query = PurchaseReturnLine::query()
            ->where('source_line_type', 'goods_receipt_note_line')
            ->where('source_line_id', $sourceLineId)
            ->whereHas('purchaseReturn', fn ($scope) => $scope->where('status', PurchaseReturnStatus::Posted->value));

        if ($currentReturnId !== null) {
            $query->where('purchase_return_id', '!=', $currentReturnId);
        }

        $lines = $query->get(['base_amount', 'discount_amount', 'tax_amount', 'charge_amount', 'line_total']);

        return [
            'base_amount' => $this->math->sum($lines->pluck('base_amount')->map(fn ($value): string => (string) $value)->all()),
            'discount_amount' => $this->math->sum($lines->pluck('discount_amount')->map(fn ($value): string => (string) $value)->all()),
            'tax_amount' => $this->math->sum($lines->pluck('tax_amount')->map(fn ($value): string => (string) $value)->all()),
            'charge_amount' => $this->math->sum($lines->pluck('charge_amount')->map(fn ($value): string => (string) $value)->all()),
            'line_total' => $this->math->sum($lines->pluck('line_total')->map(fn ($value): string => (string) $value)->all()),
        ];
    }

    private function residual(string $sourceAmount, string $postedAmount, string $label): string
    {
        $amount = $this->math->sub($sourceAmount, $postedAmount);
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException("Purchase return {$label} cannot exceed source receipt {$label}.");
        }

        return $amount;
    }

    private function costBasis(GoodsReceiptNoteLine $sourceLine, bool $allowReadQueries): string
    {
        if ($allowReadQueries) {
            $sourceLine->loadMissing('inventoryMovement');
        }

        if ($sourceLine->relationLoaded('inventoryMovement')
            && $sourceLine->inventoryMovement instanceof InventoryMovement
            && $sourceLine->inventoryMovement->unit_cost !== null) {
            return $this->math->normalize((string) $sourceLine->inventoryMovement->unit_cost);
        }

        return $this->math->normalize((string) $sourceLine->unit_price);
    }
}
