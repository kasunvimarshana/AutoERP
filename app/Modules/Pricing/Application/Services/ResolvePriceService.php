<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Application\DTOs\ResolvePriceData;
use Modules\Pricing\Domain\Exceptions\NoApplicablePriceFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class ResolvePriceService implements ResolvePriceServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(array $data = []): array
    {
        $dto = ResolvePriceData::fromArray($data);
        $priceDate = $dto->price_date !== null ? new \DateTimeImmutable($dto->price_date) : new \DateTimeImmutable;

        $match = $this->priceListItemRepository->findBestMatch(
            tenantId: $dto->tenant_id,
            type: $dto->type,
            productId: $dto->product_id,
            variantId: $dto->variant_id,
            uomId: $dto->uom_id,
            quantity: $dto->quantity,
            currencyId: $dto->currency_id,
            customerId: $dto->customer_id,
            supplierId: $dto->supplier_id,
            priceDate: $priceDate,
        );

        if ($match === null) {
            throw new NoApplicablePriceFoundException;
        }

        $basePrice = number_format((float) ($match['price'] ?? 0), 6, '.', '');
        $discountPct = number_format((float) ($match['discount_pct'] ?? 0), 6, '.', '');
        $unitPrice = bcmul($basePrice, bcsub('1.000000', bcdiv($discountPct, '100.000000', 6), 6), 6);
        $qty = number_format((float) $dto->quantity, 6, '.', '');

        return [
            'tenant_id' => $dto->tenant_id,
            'type' => $dto->type,
            'product_id' => $dto->product_id,
            'variant_id' => $dto->variant_id,
            'uom_id' => $dto->uom_id,
            'quantity' => $qty,
            'currency_id' => $dto->currency_id,
            'price_list_id' => (int) $match['price_list_id'],
            'price_list_item_id' => (int) $match['id'],
            'base_price' => $basePrice,
            'discount_pct' => $discountPct,
            'unit_price' => $unitPrice,
            'total_price' => bcmul($unitPrice, $qty, 6),
            'matched_at' => (new \DateTimeImmutable)->format('c'),
        ];
    }
}
