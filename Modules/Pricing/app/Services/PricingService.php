<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Modules\Pricing\Enums\PriceType;
use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Pricing\Repositories\PriceListRepository;
use Modules\Pricing\Repositories\TaxRateRepository;
use Modules\Pricing\Strategies\CustomerGroupPriceStrategy;
use Modules\Pricing\Strategies\FlatPriceStrategy;
use Modules\Pricing\Strategies\LocationBasedPriceStrategy;
use Modules\Pricing\Strategies\PercentagePriceStrategy;
use Modules\Pricing\Strategies\RulesBasedPriceStrategy;
use Modules\Pricing\Strategies\TieredPriceStrategy;

/**
 * Pricing Service
 *
 * Main pricing calculation service orchestrating different strategies
 */
class PricingService extends BaseService
{
    private array $strategies = [];

    public function __construct(
        private readonly PriceListRepository $priceListRepository,
        private readonly TaxRateRepository $taxRateRepository,
        private readonly DiscountService $discountService,
        FlatPriceStrategy $flatStrategy,
        PercentagePriceStrategy $percentageStrategy,
        TieredPriceStrategy $tieredStrategy,
        RulesBasedPriceStrategy $rulesStrategy,
        LocationBasedPriceStrategy $locationStrategy,
        CustomerGroupPriceStrategy $customerGroupStrategy
    ) {
        parent::__construct($priceListRepository);

        $this->registerStrategy(PriceType::FLAT->value, $flatStrategy);
        $this->registerStrategy(PriceType::PERCENTAGE->value, $percentageStrategy);
        $this->registerStrategy(PriceType::TIERED->value, $tieredStrategy);
        $this->registerStrategy(PriceType::RULES_BASED->value, $rulesStrategy);
        $this->registerStrategy(PriceType::LOCATION_BASED->value, $locationStrategy);
        $this->registerStrategy(PriceType::CUSTOMER_GROUP->value, $customerGroupStrategy);
    }

    /**
     * Register a pricing strategy
     */
    private function registerStrategy(string $type, PricingEngineInterface $strategy): void
    {
        $this->strategies[$type] = $strategy;
    }

    /**
     * Calculate price for product
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ServiceException
     */
    public function calculatePrice(int $productId, string $quantity = '1', array $context = []): array
    {
        $strategyType = $context['strategy'] ?? PriceType::FLAT->value;

        if (! isset($this->strategies[$strategyType])) {
            throw new ServiceException("Invalid pricing strategy: {$strategyType}");
        }

        $strategy = $this->strategies[$strategyType];

        // Calculate base price using strategy
        $result = $strategy->calculatePrice($productId, $quantity, $context);

        // Apply discounts if applicable
        if (isset($context['discount_code']) || isset($context['apply_auto_discounts'])) {
            $result = $this->applyDiscounts($result, $context);
        }

        // Calculate tax if applicable
        if (isset($context['calculate_tax']) && $context['calculate_tax']) {
            $result = $this->calculateTax($result, $context);
        }

        return $result;
    }

    /**
     * Calculate price for multiple products (cart/order)
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculateCartPrice(array $items, array $context = []): array
    {
        $itemPrices = [];
        $subtotal = '0.00';

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'] ?? '1';

            $itemContext = array_merge($context, $item['context'] ?? []);
            $priceResult = $this->calculatePrice($productId, $quantity, $itemContext);

            $itemPrices[] = $priceResult;
            $subtotal = bcadd($subtotal, $priceResult['subtotal'], 2);
        }

        $discount = '0.00';
        $tax = '0.00';

        // Apply cart-level discounts
        if (isset($context['discount_code'])) {
            $discountResult = $this->discountService->calculateCartDiscount(
                $subtotal,
                $itemPrices,
                $context['discount_code']
            );
            $discount = $discountResult['discount_amount'];
        }

        // Calculate tax on discounted subtotal
        if (isset($context['calculate_tax']) && $context['calculate_tax']) {
            $taxableAmount = bcsub($subtotal, $discount, 2);
            $taxResult = $this->calculateCartTax($taxableAmount, $context);
            $tax = $taxResult['tax_amount'];
        }

        $total = bcadd(bcsub($subtotal, $discount, 2), $tax, 2);

        return [
            'items' => $itemPrices,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'currency' => $context['currency'] ?? 'USD',
        ];
    }

    /**
     * Apply discounts to price result
     *
     * @param  array<string, mixed>  $priceResult
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function applyDiscounts(array $priceResult, array $context): array
    {
        $discountAmount = '0.00';
        $appliedDiscounts = [];

        if (isset($context['discount_code'])) {
            $result = $this->discountService->calculateDiscount(
                $priceResult['subtotal'],
                $context['discount_code'],
                $context
            );

            if ($result) {
                $discountAmount = $result['discount_amount'];
                $appliedDiscounts[] = $result;
            }
        }

        $priceResult['discount'] = $discountAmount;
        $priceResult['applied_discounts'] = $appliedDiscounts;
        $priceResult['subtotal_after_discount'] = bcsub($priceResult['subtotal'], $discountAmount, 2);

        return $priceResult;
    }

    /**
     * Calculate tax
     *
     * @param  array<string, mixed>  $priceResult
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function calculateTax(array $priceResult, array $context): array
    {
        $jurisdiction = $context['jurisdiction'] ?? null;
        $productCategory = $context['product_category'] ?? null;

        $taxableAmount = $priceResult['subtotal_after_discount'] ?? $priceResult['subtotal'];
        $taxAmount = '0.00';
        $appliedTaxes = [];

        if ($jurisdiction && $productCategory) {
            $taxRate = $this->taxRateRepository->findForJurisdictionAndCategory($jurisdiction, $productCategory);
        } elseif ($jurisdiction) {
            $taxRate = $this->taxRateRepository->findForJurisdiction($jurisdiction);
        } elseif ($productCategory) {
            $taxRate = $this->taxRateRepository->findForProductCategory($productCategory);
        } else {
            $taxRate = null;
        }

        if ($taxRate) {
            $taxAmount = $taxRate->calculateTax($taxableAmount);
            $appliedTaxes[] = [
                'tax_id' => $taxRate->id,
                'tax_name' => $taxRate->name,
                'tax_rate' => (string) $taxRate->rate,
                'tax_amount' => $taxAmount,
            ];
        }

        $priceResult['tax'] = $taxAmount;
        $priceResult['applied_taxes'] = $appliedTaxes;
        $priceResult['total'] = bcadd($taxableAmount, $taxAmount, 2);

        return $priceResult;
    }

    /**
     * Calculate cart-level tax
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function calculateCartTax(string $taxableAmount, array $context): array
    {
        $jurisdiction = $context['jurisdiction'] ?? null;
        $taxAmount = '0.00';
        $appliedTaxes = [];

        if ($jurisdiction) {
            $taxRate = $this->taxRateRepository->findForJurisdiction($jurisdiction);

            if ($taxRate) {
                $taxAmount = $taxRate->calculateTax($taxableAmount);
                $appliedTaxes[] = [
                    'tax_id' => $taxRate->id,
                    'tax_name' => $taxRate->name,
                    'tax_rate' => (string) $taxRate->rate,
                    'tax_amount' => $taxAmount,
                ];
            }
        }

        return [
            'tax_amount' => $taxAmount,
            'applied_taxes' => $appliedTaxes,
        ];
    }

    /**
     * Get available pricing strategies
     *
     * @return array<string>
     */
    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }
}
