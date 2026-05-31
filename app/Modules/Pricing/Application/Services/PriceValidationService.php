<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Pricing\Application\Contracts\Services\PriceValidationServiceInterface;
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class PriceValidationService implements PriceValidationServiceInterface
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly PriceListItemRepositoryInterface $priceItems,
        private readonly PricingRuleRepositoryInterface $rules,
        private readonly DiscountRepositoryInterface $discounts,
        private readonly ItemRepositoryInterface $items,
        private readonly UnitOfMeasureRepositoryInterface $uoms,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly CustomerRepositoryInterface $customers,
        private readonly SupplierRepositoryInterface $suppliers,
    ) {
    }

    public function validatePriceList(array $payload, bool $isUpdate = false): Result
    {
        return $this->guard(fn (): Result => $this->validatePriceListPayload($payload, $isUpdate));
    }

    public function validatePriceListItem(array $payload, bool $isUpdate = false): Result
    {
        return $this->guard(fn (): Result => $this->validatePriceListItemPayload($payload));
    }

    public function validatePricingRule(array $payload, bool $isUpdate = false): Result
    {
        return $this->guard(fn (): Result => $this->validatePricingRulePayload($payload, $isUpdate));
    }

    public function validateDiscount(array $payload, bool $isUpdate = false): Result
    {
        return $this->guard(fn (): Result => $this->validateDiscountPayload($payload, $isUpdate));
    }

    public function validatePriceTier(array $payload, bool $isUpdate = false): Result
    {
        return $this->guard(fn (): Result => $this->validatePriceTierPayload($payload));
    }

    public function validateResolveContext(array $context): Result
    {
        return $this->guard(fn (): Result => $this->validateResolveContextPayload($context));
    }

    private function validatePriceListPayload(array $payload, bool $isUpdate): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($tenantId < 1 || (! $isUpdate && $name === '')) {
            return $this->invalid('tenant_id and name are required.');
        }

        if (! $isUpdate && $name !== '' && $this->priceLists->exists(['tenant_id' => $tenantId, 'name' => $name])) {
            return $this->conflict('Price list name already exists in tenant.');
        }

        if (! $this->currencyExists($payload['currency_id'] ?? null)) {
            return $this->notFound('Currency not found.');
        }

        if (! $this->validDateRange($payload['valid_from'] ?? null, $payload['valid_to'] ?? null)) {
            return $this->invalid('valid_from cannot be after valid_to.');
        }

        return Result::success(null);
    }

    private function validatePriceListItemPayload(array $payload): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $priceListId = (int) ($payload['price_list_id'] ?? 0);
        $itemId = (int) ($payload['item_id'] ?? 0);
        $uomId = (int) ($payload['uom_id'] ?? 0);
        $price = (float) ($payload['price'] ?? -1);

        if ($tenantId < 1 || $priceListId < 1 || $itemId < 1 || $uomId < 1) {
            return $this->invalid('tenant_id, price_list_id, item_id and uom_id are required.');
        }

        if (! $this->itemExistsInTenant($itemId, $tenantId)) {
            return $this->notFound('Item not found in tenant.');
        }

        if (! $this->priceLists->findById($priceListId) instanceof DataRecord) {
            return $this->notFound('Price list not found.');
        }

        if (! $this->uomExists($uomId)) {
            return $this->notFound('Unit of measure not found.');
        }

        if (! $this->currencyExists($payload['currency_id'] ?? null)) {
            return $this->notFound('Currency not found.');
        }

        if ($price < 0) {
            return $this->invalid('price must be >= 0.');
        }

        $minQuantity = (float) ($payload['min_quantity'] ?? 1);
        $maxQuantity = $payload['max_quantity'] ?? null;
        $maxQuantity = $maxQuantity !== null ? (float) $maxQuantity : null;
        if ($minQuantity <= 0) {
            return $this->invalid('min_quantity must be > 0.');
        }

        if ($maxQuantity !== null && $maxQuantity < $minQuantity) {
            return $this->invalid('max_quantity cannot be less than min_quantity.');
        }

        foreach (
            $this->priceItems->list([
                'tenant_id' => $tenantId,
                'price_list_id' => $priceListId,
                'item_id' => $itemId,
                'uom_id' => $uomId,
                'is_active' => true,
            ]) as $record
        ) {
            if ($record instanceof DataRecord) {
                $existingMin = (float) $record->get('min_quantity', 1);
                $existingMax = $record->get('max_quantity');
                $existingMax = $existingMax !== null ? (float) $existingMax : null;
                if ($this->rangesOverlap($minQuantity, $maxQuantity, $existingMin, $existingMax)) {
                    return $this->conflict('Overlapping quantity tiers are not allowed.');
                }
            }
        }

        if (! $this->validDateRange($payload['valid_from'] ?? null, $payload['valid_to'] ?? null)) {
            return $this->invalid('valid_from cannot be after valid_to.');
        }

        return Result::success(null);
    }

    private function validatePricingRulePayload(array $payload, bool $isUpdate): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($tenantId < 1 || (! $isUpdate && $name === '')) {
            return $this->invalid('tenant_id and name are required.');
        }

        if (! $isUpdate && $this->codeExists($this->rules, $tenantId, $payload['code'] ?? null)) {
            return $this->conflict('Pricing rule code already exists in tenant.');
        }

        if (! $this->itemExistsInTenant($payload['item_id'] ?? null, $tenantId)) {
            return $this->notFound('Item not found in tenant.');
        }

        if (! $this->customerExists($payload['customer_id'] ?? null)) {
            return $this->notFound('Customer not found.');
        }

        if (! $this->supplierExists($payload['supplier_id'] ?? null)) {
            return $this->notFound('Supplier not found.');
        }

        if (! $this->uomExists($payload['uom_id'] ?? null)) {
            return $this->notFound('Unit of measure not found.');
        }

        if (! $this->currencyExists($payload['currency_id'] ?? null)) {
            return $this->notFound('Currency not found.');
        }

        return Result::success(null);
    }

    private function validateDiscountPayload(array $payload, bool $isUpdate): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $discountValue = (float) ($payload['discount_value'] ?? -1);
        if ($tenantId < 1 || (! $isUpdate && $name === '')) {
            return $this->invalid('tenant_id and name are required.');
        }

        if (! $isUpdate && $this->codeExists($this->discounts, $tenantId, $payload['code'] ?? null)) {
            return $this->conflict('Discount code already exists in tenant.');
        }

        if (array_key_exists('discount_value', $payload) && $discountValue < 0) {
            return $this->invalid('discount_value must be >= 0.');
        }

        return Result::success(null);
    }

    private function validatePriceTierPayload(array $payload): Result
    {
        $minQuantity = (float) ($payload['min_quantity'] ?? 0);
        $maxQuantity = $payload['max_quantity'] ?? null;
        $maxQuantity = $maxQuantity !== null ? (float) $maxQuantity : null;

        if ($minQuantity <= 0) {
            return $this->invalid('min_quantity must be > 0.');
        }

        if ($maxQuantity !== null && $maxQuantity < $minQuantity) {
            return $this->invalid('max_quantity cannot be less than min_quantity.');
        }

        if (($payload['price'] ?? null) === null && ($payload['adjustment_value'] ?? null) === null) {
            return $this->invalid('price or adjustment_value is required.');
        }

        return Result::success(null);
    }

    private function validateResolveContextPayload(array $context): Result
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $itemId = (int) ($context['item_id'] ?? 0);
        $quantity = (float) ($context['quantity'] ?? 0);
        if ($tenantId < 1 || $itemId < 1) {
            return $this->invalid('tenant_id and item_id are required.');
        }

        if ($quantity <= 0) {
            return $this->invalid('quantity must be > 0.');
        }

        if (! $this->itemExistsInTenant($itemId, $tenantId)) {
            return $this->notFound('Item not found in tenant.');
        }

        return Result::success(null);
    }

    private function guard(callable $callback): Result
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            return $this->invalid($exception->getMessage());
        }
    }

    private function invalid(string $message): Result
    {
        return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $message));
    }

    private function notFound(string $message): Result
    {
        return Result::failure(new Error(PricingErrorCode::NOT_FOUND, $message));
    }

    private function conflict(string $message): Result
    {
        return Result::failure(new Error(PricingErrorCode::CONFLICT, $message));
    }

    private function currencyExists(mixed $currencyId): bool
    {
        return $currencyId === null || $this->currencies->findById((int) $currencyId) instanceof DataRecord;
    }

    private function uomExists(mixed $uomId): bool
    {
        return $uomId === null || $this->uoms->findById((int) $uomId) instanceof DataRecord;
    }

    private function itemExistsInTenant(mixed $itemId, int $tenantId): bool
    {
        return $itemId === null || $this->items->findByIdInTenant((int) $itemId, $tenantId) instanceof DataRecord;
    }

    private function customerExists(mixed $customerId): bool
    {
        return $customerId === null || $this->customers->findById((int) $customerId) instanceof DataRecord;
    }

    private function supplierExists(mixed $supplierId): bool
    {
        return $supplierId === null || $this->suppliers->findById((int) $supplierId) instanceof DataRecord;
    }

    private function codeExists(object $repository, int $tenantId, mixed $code): bool
    {
        return is_string($code) && trim($code) !== ''
            && $repository->exists(['tenant_id' => $tenantId, 'code' => $code]);
    }

    private function validDateRange(mixed $from, mixed $to): bool
    {
        if ($from === null || $to === null || $from === '' || $to === '') {
            return true;
        }

        return strtotime((string) $from) <= strtotime((string) $to);
    }

    private function rangesOverlap(float $minA, ?float $maxA, float $minB, ?float $maxB): bool
    {
        $endA = $maxA ?? $minA;
        $endB = $maxB ?? $minB;

        return $minA <= $endB && $minB <= $endA;
    }
}
