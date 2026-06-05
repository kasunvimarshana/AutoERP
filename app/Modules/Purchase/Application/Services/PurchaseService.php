<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeader;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturn;

final class PurchaseService
{
    public function __construct(private readonly PurchaseCalculationService $calculator)
    {
    }

    public function createPurchaseOrder(array $payload): PurchaseOrder
    {
        return DB::transaction(fn (): PurchaseOrder => $this->storeDocument(
            new PurchaseOrder(),
            $payload,
            'lines',
            'ordered_qty',
        ));
    }

    public function updatePurchaseOrder(PurchaseOrder $purchaseOrder, array $payload): PurchaseOrder
    {
        return DB::transaction(fn (): PurchaseOrder => $this->storeDocument(
            $purchaseOrder,
            $payload,
            'lines',
            'ordered_qty',
        ));
    }

    public function createGrn(array $payload): GrnHeader
    {
        return DB::transaction(fn (): GrnHeader => $this->storeDocument(
            new GrnHeader(),
            $payload,
            'lines',
            'received_qty',
        ));
    }

    public function createPurchaseReturn(array $payload): PurchaseReturn
    {
        return DB::transaction(fn (): PurchaseReturn => $this->storeDocument(
            new PurchaseReturn(),
            $payload,
            'lines',
            'return_qty',
            true,
        ));
    }

    private function storeDocument(
        PurchaseOrder|GrnHeader|PurchaseReturn $document,
        array $payload,
        string $linesKey,
        string $quantityField,
        bool $isReturn = false,
    ): PurchaseOrder|GrnHeader|PurchaseReturn {
        $lines = is_array($payload[$linesKey] ?? null) ? $payload[$linesKey] : [];
        unset($payload[$linesKey]);

        $calculatedLines = array_map(
            fn (array $line): array => $this->calculator->calculateLine($line, $quantityField),
            array_filter($lines, 'is_array'),
        );

        $payload = array_merge($payload, $this->calculator->calculateTotals($calculatedLines, $payload, $isReturn));
        $document->fill($payload);
        $document->save();

        if ($calculatedLines !== []) {
            $document->lines()->delete();
            $document->lines()->createMany($calculatedLines);
        }

        return $document->refresh();
    }
}
