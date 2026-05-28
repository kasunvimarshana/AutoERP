<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PriceValidationServiceInterface
{
    /** @param array<string, mixed> $payload */
    public function validatePriceList(array $payload, bool $isUpdate = false): Result;

    /** @param array<string, mixed> $payload */
    public function validatePriceListItem(array $payload, bool $isUpdate = false): Result;

    /** @param array<string, mixed> $payload */
    public function validatePricingRule(array $payload, bool $isUpdate = false): Result;

    /** @param array<string, mixed> $payload */
    public function validateDiscount(array $payload, bool $isUpdate = false): Result;

    /** @param array<string, mixed> $payload */
    public function validatePriceTier(array $payload, bool $isUpdate = false): Result;

    /** @param array<string, mixed> $context */
    public function validateResolveContext(array $context): Result;
}
