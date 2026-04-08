<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Pricing\Application\Contracts\PriceRuleServiceInterface;
use Modules\Pricing\Domain\Entities\PriceRule;
use Modules\Pricing\Domain\Events\PriceRuleCreated;
use Modules\Pricing\Domain\Events\PriceRuleUpdated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceRuleRepositoryInterface;

class PriceRuleService implements PriceRuleServiceInterface
{
    public function __construct(
        private readonly PriceRuleRepositoryInterface $priceRuleRepository,
    ) {}

    public function getPriceRule(string $tenantId, string $id): PriceRule
    {
        $priceRule = $this->priceRuleRepository->findById($tenantId, $id);
        if ($priceRule === null) {
            throw new NotFoundException('PriceRule', $id);
        }
        return $priceRule;
    }

    public function getRulesForPriceList(string $tenantId, string $priceListId): array
    {
        return $this->priceRuleRepository->findByPriceList($tenantId, $priceListId);
    }

    public function createPriceRule(string $tenantId, array $data): PriceRule
    {
        return DB::transaction(function () use ($tenantId, $data): PriceRule {
            $now = now();
            $priceRule = new PriceRule(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                priceListId: $data['price_list_id'],
                productId: $data['product_id'] ?? null,
                categoryId: $data['category_id'] ?? null,
                variantId: $data['variant_id'] ?? null,
                minQty: (float) ($data['min_qty'] ?? 1.0),
                price: (float) $data['price'],
                discountPercent: (float) ($data['discount_percent'] ?? 0.0),
                startDate: isset($data['start_date']) ? new \DateTime($data['start_date']) : null,
                endDate: isset($data['end_date']) ? new \DateTime($data['end_date']) : null,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->priceRuleRepository->save($priceRule);
            Event::dispatch(new PriceRuleCreated($priceRule));
            return $priceRule;
        });
    }

    public function updatePriceRule(string $tenantId, string $id, array $data): PriceRule
    {
        return DB::transaction(function () use ($tenantId, $id, $data): PriceRule {
            $existing = $this->getPriceRule($tenantId, $id);
            $priceRule = new PriceRule(
                id: $existing->id,
                tenantId: $existing->tenantId,
                priceListId: $data['price_list_id'] ?? $existing->priceListId,
                productId: array_key_exists('product_id', $data) ? $data['product_id'] : $existing->productId,
                categoryId: array_key_exists('category_id', $data) ? $data['category_id'] : $existing->categoryId,
                variantId: array_key_exists('variant_id', $data) ? $data['variant_id'] : $existing->variantId,
                minQty: isset($data['min_qty']) ? (float) $data['min_qty'] : $existing->minQty,
                price: isset($data['price']) ? (float) $data['price'] : $existing->price,
                discountPercent: isset($data['discount_percent']) ? (float) $data['discount_percent'] : $existing->discountPercent,
                startDate: array_key_exists('start_date', $data)
                    ? ($data['start_date'] !== null ? new \DateTime($data['start_date']) : null)
                    : $existing->startDate,
                endDate: array_key_exists('end_date', $data)
                    ? ($data['end_date'] !== null ? new \DateTime($data['end_date']) : null)
                    : $existing->endDate,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->priceRuleRepository->save($priceRule);
            Event::dispatch(new PriceRuleUpdated($priceRule));
            return $priceRule;
        });
    }

    public function deletePriceRule(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getPriceRule($tenantId, $id);
            $this->priceRuleRepository->delete($tenantId, $id);
        });
    }

    public function resolvePrice(
        string $tenantId,
        string $priceListId,
        ?string $productId,
        ?string $variantId,
        ?string $categoryId,
        float $qty
    ): float {
        $rules = $this->priceRuleRepository->findByPriceList($tenantId, $priceListId);
        $now = now();

        $matching = array_filter($rules, function (PriceRule $rule) use ($productId, $variantId, $categoryId, $qty, $now): bool {
            if ($rule->productId !== null && $rule->productId !== $productId) {
                return false;
            }
            if ($rule->variantId !== null && $rule->variantId !== $variantId) {
                return false;
            }
            if ($rule->categoryId !== null && $rule->categoryId !== $categoryId) {
                return false;
            }
            if ($rule->minQty > $qty) {
                return false;
            }
            if ($rule->startDate !== null && $now->lt($rule->startDate)) {
                return false;
            }
            if ($rule->endDate !== null && $now->gt($rule->endDate)) {
                return false;
            }
            return true;
        });

        if (empty($matching)) {
            return 0.0;
        }

        usort($matching, function (PriceRule $a, PriceRule $b): int {
            return $this->specificity($b) <=> $this->specificity($a);
        });

        $best = reset($matching);

        $discounted = $best->price * (1 - $best->discountPercent / 100);
        return round($discounted, 4);
    }

    private function specificity(PriceRule $rule): int
    {
        if ($rule->variantId !== null) {
            return 3;
        }
        if ($rule->productId !== null) {
            return 2;
        }
        if ($rule->categoryId !== null) {
            return 1;
        }
        return 0;
    }
}
