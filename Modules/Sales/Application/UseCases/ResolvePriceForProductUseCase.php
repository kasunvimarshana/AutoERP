<?php
namespace Modules\Sales\Application\UseCases;

use DomainException;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Domain\Enums\PricingStrategy;

/**
 * Resolves the effective unit price for a product given a price list, quantity, and base price.
 *
 * Resolution rules:
 *  - Finds the most specific applicable item for the product (optionally variant-scoped).
 *  - Selects the item where min_qty <= requested qty (highest min_qty wins for tiered).
 *  - strategy=flat      → returns item amount as the resolved price (BCMath).
 *  - strategy=percentage_discount → applies discount% off the provided base_price.
 */
class ResolvePriceForProductUseCase
{
    public function __construct(private PriceListRepositoryInterface $repo) {}

    /**
     * @param  string      $priceListId
     * @param  string      $productId
     * @param  string|null $variantId
     * @param  string      $qty          BCMath-compatible quantity string
     * @param  string      $basePrice    BCMath-compatible unit price string (used for % discount)
     * @return string  Resolved unit price (DECIMAL(18,8) string)
     */
    public function execute(
        string  $priceListId,
        string  $productId,
        ?string $variantId,
        string  $qty,
        string  $basePrice,
    ): string {
        $priceList = $this->repo->findById($priceListId);
        if (!$priceList) {
            throw new DomainException('Price list not found.');
        }

        $item = $this->repo->findItem($priceListId, $productId, $variantId, $qty);
        if (!$item) {
            // No applicable rule — return the base price unchanged.
            return bcadd($basePrice, '0', 8);
        }

        return match ($item->strategy) {
            PricingStrategy::Flat->value => bcadd($item->amount, '0', 8),
            PricingStrategy::PercentageDiscount->value => bcsub(
                $basePrice,
                bcmul($basePrice, bcdiv($item->amount, '100', 10), 8),
                8,
            ),
            default => bcadd($basePrice, '0', 8),
        };
    }
}
