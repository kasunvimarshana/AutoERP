<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Purchase\Constants\PurchaseFinanceSource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;

final class PurchaseGoodsReceiptFinanceService
{
    private const ZERO = '0.000000';

    private const BASE_EXCHANGE_RATE = '1.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseAcquisitionCostAllocator $costs,
        private readonly FinancePostingInterface $postings,
        private readonly FinanceSourceReversalInterface $reversals,
    ) {}

    public function post(GoodsReceiptNote $goodsReceipt, ?int $actorId = null): ?PostingResultData
    {
        $amount = $this->math->normalize($this->costs->goodsReceiptStockValue($goodsReceipt));
        if ($this->math->isZero($amount)) {
            return null;
        }

        $goodsReceipt->loadMissing('purchaseOrder');
        $purchaseOrder = $goodsReceipt->purchaseOrder;

        return $this->postings->post(new PostingContext(
            source: new PostingSourceData(
                sourceType: PurchaseFinanceSource::GOODS_RECEIPT,
                sourceId: (int) $goodsReceipt->getKey(),
                tenantId: (int) $goodsReceipt->tenant_id,
                organizationUnitId: $goodsReceipt->organization_unit_id,
                sourceModule: PurchaseFinanceSource::MODULE,
                sourceNumber: (string) $goodsReceipt->grn_number,
                sourceDate: $goodsReceipt->received_date->toDateString(),
            ),
            postingDate: $goodsReceipt->received_date->toDateString(),
            currencyId: $purchaseOrder instanceof PurchaseOrder ? $purchaseOrder->currency_id : null,
            exchangeRate: $purchaseOrder instanceof PurchaseOrder
                ? (string) $purchaseOrder->exchange_rate
                : self::BASE_EXCHANGE_RATE,
            lines: [
                new PostingLine(
                    lineName: 'Inventory received',
                    debit: $amount,
                    credit: self::ZERO,
                    description: 'Inventory received before supplier invoicing',
                    profileKey: FinanceAccountRoleCode::Inventory->value,
                    sourceLineType: PurchaseFinanceSource::GOODS_RECEIPT,
                    sourceLineId: (int) $goodsReceipt->getKey(),
                ),
                new PostingLine(
                    lineName: 'Goods received not invoiced',
                    debit: self::ZERO,
                    credit: $amount,
                    description: 'Goods received not invoiced liability',
                    profileKey: FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value,
                    sourceLineType: PurchaseFinanceSource::GOODS_RECEIPT,
                    sourceLineId: (int) $goodsReceipt->getKey(),
                ),
            ],
            description: 'Goods receipt '.$goodsReceipt->grn_number,
            postingProfileCode: FinancePostingProfileCode::InventoryReceipt->value,
        ), $actorId);
    }

    public function reverse(
        GoodsReceiptNote $goodsReceipt,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
    ): ?PostingResultData {
        $amount = $this->math->normalize($this->costs->goodsReceiptStockValue($goodsReceipt));
        if ($this->math->isZero($amount)) {
            return null;
        }

        return $this->reversals->reverseSource(
            (int) $goodsReceipt->tenant_id,
            $goodsReceipt->organization_unit_id,
            PurchaseFinanceSource::MODULE,
            PurchaseFinanceSource::GOODS_RECEIPT,
            (int) $goodsReceipt->getKey(),
            $reversalDate,
            $actorId,
            $reason,
        );
    }
}
