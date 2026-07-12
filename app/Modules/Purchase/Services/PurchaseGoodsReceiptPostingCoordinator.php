<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;

final class PurchaseGoodsReceiptPostingCoordinator
{
    public function __construct(
        private readonly GoodsReceiptNoteService $goodsReceipts,
        private readonly PurchaseGoodsReceiptFinanceService $finance,
    ) {}

    public function post(
        GoodsReceiptNote $goodsReceipt,
        ?int $actorId = null,
        ?int $expectedVersion = null,
    ): GoodsReceiptNote {
        return DB::transaction(function () use ($goodsReceipt, $actorId, $expectedVersion): GoodsReceiptNote {
            $posted = $this->goodsReceipts->post($goodsReceipt, $actorId, $expectedVersion);
            $this->finance->post($posted, $actorId);

            return $posted->refresh()->load(['lines.inventoryMovement', 'adjustments']);
        });
    }

    public function reverse(
        GoodsReceiptNote $goodsReceipt,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
        ?int $expectedVersion = null,
    ): GoodsReceiptNote {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Goods receipt reversal reason is required.');
        }
        try {
            $normalizedDate = CarbonImmutable::parse($reversalDate)->toDateString();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Goods receipt reversal date is invalid.');
        }
        if ($normalizedDate !== $reversalDate) {
            throw new InvalidArgumentException('Goods receipt reversal date must use YYYY-MM-DD format.');
        }

        return DB::transaction(function () use ($goodsReceipt, $reversalDate, $reason, $actorId, $expectedVersion): GoodsReceiptNote {
            $current = GoodsReceiptNote::query()->findOrFail($goodsReceipt->getKey());
            if ($reversalDate < $current->received_date->toDateString()) {
                throw new InvalidArgumentException('Goods receipt reversal date cannot be before the receipt date.');
            }
            if ($current->status === GoodsReceiptNoteStatus::Reversed) {
                return $current->load(['lines.inventoryMovement', 'adjustments']);
            }

            $reversed = $this->goodsReceipts->reverse($current, $actorId, $expectedVersion);
            $this->finance->reverse($reversed, $reversalDate, $reason, $actorId);

            return $reversed->refresh()->load(['lines.inventoryMovement', 'adjustments']);
        });
    }
}
