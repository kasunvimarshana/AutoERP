<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\Services\PriceValidationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PricingRuleServiceInterface;
use Modules\Pricing\Application\Repositories\PricingRuleConditionRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class PricingRuleService implements PricingRuleServiceInterface
{
    public function __construct(
        private readonly PricingRuleRepositoryInterface $pricingRuleRepository,
        private readonly PricingRuleConditionRepositoryInterface $pricingRuleConditionRepository,
        private readonly PriceValidationServiceInterface $validationService,
    ) {
    }

    public function createPricingRule(array $payload): Result
    {
        try {
            $validation = $this->validationService->validatePricingRule($payload, false);
            if ($validation->isFailure()) {
                return $validation;
            }

            $conditions = is_array($payload['conditions'] ?? null) ? $payload['conditions'] : [];
            unset($payload['conditions']);
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return $this->pricingRuleRepository->transaction(function () use ($payload, $conditions): Result {
                $rule = $this->pricingRuleRepository->create($payload);
                $this->syncConditions((int) $rule->id(), $payload, $conditions);

                return Result::success($rule);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updatePricingRule(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->pricingRuleRepository->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'Pricing rule not found.'));
            }

            $payload['tenant_id'] = (int) $existing->get('tenant_id');
            $validation = $this->validationService->validatePricingRule($payload, true);
            if ($validation->isFailure()) {
                return $validation;
            }

            $conditions = array_key_exists('conditions', $payload)
                && is_array($payload['conditions'])
                ? $payload['conditions']
                : null;
            unset($payload['conditions']);

            return $this->pricingRuleRepository->transaction(function () use ($id, $payload, $conditions): Result {
                $rule = $this->pricingRuleRepository->update($id, $payload);
                if (is_array($conditions)) {
                    $this->syncConditions((int) $rule->id(), $payload, $conditions);
                }

                return Result::success($rule);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $conditions
     * @param array<string, mixed> $payload
     */
    private function syncConditions(int $ruleId, array $payload, array $conditions): void
    {
        foreach ($this->pricingRuleConditionRepository->list(['pricing_rule_id' => $ruleId]) as $existingCondition) {
            if ($existingCondition instanceof DataRecord) {
                $this->pricingRuleConditionRepository->delete($existingCondition->id());
            }
        }

        $sequence = 1;
        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $condition['tenant_id'] = (int) $payload['tenant_id'];
            $condition['pricing_rule_id'] = $ruleId;
            $condition['sequence'] = (int) ($condition['sequence'] ?? $sequence++);
            $condition['row_version'] = 1;
            $this->pricingRuleConditionRepository->create($condition);
        }
    }
}
