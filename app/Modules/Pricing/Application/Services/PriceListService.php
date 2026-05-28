<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\Services\PriceListServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceValidationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PricingRuleServiceInterface;
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceHistoryRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingTierRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use RuntimeException;
use Throwable;

final class PriceListService implements PriceListServiceInterface
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceListRepository,
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
        private readonly PricingRuleServiceInterface $pricingRuleService,
        private readonly PriceValidationServiceInterface $validationService,
        private readonly PricingTierRepositoryInterface $pricingTierRepository,
        private readonly DiscountRepositoryInterface $discountRepository,
        private readonly PriceHistoryRepositoryInterface $priceHistoryRepository,
    ) {
    }

    public function createPriceList(array $payload): Result
    {
        try {
            $validation = $this->validationService->validatePriceList($payload, false);
            if ($validation->isFailure()) {
                return $validation;
            }

            $items = $this->popNested($payload, 'items');
            $rules = $this->popNested($payload, 'rules');
            $discounts = $this->popNested($payload, 'discounts');

            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return $this->priceListRepository->transaction(
                function () use ($payload, $items, $rules, $discounts): Result {
                    if ($this->hasActiveDefaultPriceList($payload)) {
                        return $this->conflict('Only one active default price list is allowed per tenant.');
                    }

                    $priceList = $this->priceListRepository->create($payload);
                    $this->logHistory($payload, $priceList);
                    $this->syncNestedChildren($priceList, $items, $rules, $discounts);

                    return Result::success($priceList);
                }
            );
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updatePriceList(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->priceListRepository->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'Price list not found.'));
            }

            $payload['tenant_id'] = (int) $existing->get('tenant_id');
            $validation = $this->validationService->validatePriceList($payload, true);
            if ($validation->isFailure()) {
                return $validation;
            }

            $items = $this->nestedArray($payload, 'items');
            $rules = $this->nestedArray($payload, 'rules');
            $discounts = $this->nestedArray($payload, 'discounts');

            return $this->priceListRepository->transaction(
                function () use ($id, $payload, $existing, $items, $rules, $discounts): Result {
                    if ($this->hasActiveDefaultPriceList($payload, (int) $existing->id())) {
                        return $this->conflict('Only one active default price list is allowed per tenant.');
                    }

                    $before = $existing->toArray();
                    $priceList = $this->priceListRepository->update($id, $payload);
                    $this->logHistory($before, $priceList);

                    if ($items !== [] || $rules !== [] || $discounts !== []) {
                        $this->syncNestedChildren($priceList, $items, $rules, $discounts);
                    }

                    return Result::success($priceList);
                }
            );
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function hasActiveDefaultPriceList(array $payload, ?int $currentId = null): bool
    {
        if (($payload['is_default'] ?? false) !== true) {
            return false;
        }

        $defaultLists = $this->priceListRepository->list([
            'tenant_id' => (int) $payload['tenant_id'],
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach ($defaultLists as $candidate) {
            if (! $candidate instanceof DataRecord) {
                continue;
            }

            if ($currentId !== null && (int) $candidate->id() === $currentId) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nestedArray(array &$payload, string $key): array
    {
        $nested = $payload[$key] ?? [];
        unset($payload[$key]);

        return is_array($nested) ? $nested : [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function popNested(array &$payload, string $key): array
    {
        $nested = $payload[$key] ?? [];
        unset($payload[$key]);

        return is_array($nested) ? $nested : [];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array<string, mixed>> $discounts
     */
    private function syncNestedChildren(DataRecord $priceList, array $items, array $rules, array $discounts): void
    {
        foreach ($items as $itemPayload) {
            if (! is_array($itemPayload)) {
                continue;
            }

            $itemPayload['tenant_id'] = (int) $priceList->get('tenant_id');
            $itemPayload['organization_unit_id'] = $this->organizationUnitId($itemPayload, $priceList);
            $itemPayload['price_list_id'] = (int) $priceList->id();

            $validation = $this->validationService->validatePriceListItem($itemPayload, false);
            if ($validation->isFailure()) {
                throw new RuntimeException($validation->errorOrFail()->message);
            }

            if (! array_key_exists('row_version', $itemPayload)) {
                $itemPayload['row_version'] = 1;
            }

            $item = $this->priceListItemRepository->create($itemPayload);
            $this->logHistory($itemPayload, $item);

            foreach ($itemPayload['tiers'] ?? [] as $tierPayload) {
                if (! is_array($tierPayload)) {
                    continue;
                }

                $tierPayload['tenant_id'] = (int) $priceList->get('tenant_id');
                $tierPayload['price_list_item_id'] = (int) $item->id();
                $tierPayload['row_version'] = 1;

                $validation = $this->validationService->validatePriceTier($tierPayload, false);
                if ($validation->isFailure()) {
                    throw new RuntimeException($validation->errorOrFail()->message);
                }

                $this->pricingTierRepository->create($tierPayload);
            }
        }

        foreach ($rules as $rulePayload) {
            if (! is_array($rulePayload)) {
                continue;
            }

            $rulePayload['tenant_id'] = (int) $priceList->get('tenant_id');
            $rulePayload['organization_unit_id'] = $this->organizationUnitId($rulePayload, $priceList);
            $rulePayload['source_type'] = $rulePayload['source_type'] ?? $priceList->get('source_type');
            $rulePayload['source_id'] = $rulePayload['source_id'] ?? $priceList->get('source_id');
            $rulePayload['row_version'] = 1;

            $result = $this->pricingRuleService->createPricingRule($rulePayload);
            if ($result->isFailure()) {
                throw new RuntimeException($result->errorOrFail()->message);
            }
        }

        foreach ($discounts as $discountPayload) {
            if (! is_array($discountPayload)) {
                continue;
            }

            $discountPayload['tenant_id'] = (int) $priceList->get('tenant_id');
            $discountPayload['organization_unit_id'] = $this->organizationUnitId($discountPayload, $priceList);
            $discountPayload['source_type'] = $discountPayload['source_type'] ?? $priceList->get('source_type');
            $discountPayload['source_id'] = $discountPayload['source_id'] ?? $priceList->get('source_id');
            $discountPayload['row_version'] = 1;

            $validation = $this->validationService->validateDiscount($discountPayload, false);
            if ($validation->isFailure()) {
                throw new RuntimeException($validation->errorOrFail()->message);
            }

            $discount = $this->discountRepository->create($discountPayload);
            $this->logHistory($discountPayload, $discount);

            foreach ($discountPayload['rules'] ?? [] as $rulePayload) {
                if (! is_array($rulePayload)) {
                    continue;
                }

                $rulePayload['tenant_id'] = (int) $priceList->get('tenant_id');
                $rulePayload['discount_id'] = (int) $discount->id();
                $rulePayload['row_version'] = 1;
                $this->pricingRuleService->createPricingRule($rulePayload);
            }
        }
    }

    private function organizationUnitId(array $payload, DataRecord $priceList): mixed
    {
        return $payload['organization_unit_id'] ?? $priceList->get('organization_unit_id');
    }

    /**
     * @param array<string, mixed> $before
     */
    private function logHistory(array $before, DataRecord $after): void
    {
        $fields = [
            'name',
            'code',
            'type',
            'scope_type',
            'source_type',
            'source_id',
            'currency_id',
            'priority',
            'is_default',
            'is_stackable',
            'is_exclusive',
            'valid_from',
            'valid_to',
            'is_active',
            'price',
            'discount_value',
        ];

        foreach ($fields as $field) {
            $old = $before[$field] ?? null;
            $new = $after->get($field);
            if ($old === $new && ! empty($before)) {
                continue;
            }

            $this->priceHistoryRepository->create([
                'tenant_id' => (int) $after->get('tenant_id'),
                'organization_unit_id' => $after->get('organization_unit_id'),
                'entity_type' => 'pricing',
                'entity_id' => (int) $after->id(),
                'field_name' => $field,
                'old_text' => is_scalar($old) ? (string) $old : null,
                'new_text' => is_scalar($new) ? (string) $new : null,
                'changed_by' => $after->get('updated_by') ?? $after->get('created_by'),
                'changed_at' => now(),
                'reason' => 'pricing sync',
                'source_type' => 'pricing',
                'source_id' => (int) $after->id(),
                'row_version' => 1,
            ]);
        }
    }

    private function conflict(string $message): Result
    {
        return Result::failure(new Error(PricingErrorCode::CONFLICT, $message));
    }
}
