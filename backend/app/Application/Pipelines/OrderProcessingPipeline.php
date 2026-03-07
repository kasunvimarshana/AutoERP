<?php

declare(strict_types=1);

namespace App\Application\Pipelines;

use App\Application\DTOs\OrderDTO;
use App\Application\Pipelines\Contracts\PipelineInterface;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

/**
 * Pipeline that validates and enriches an OrderDTO before it reaches the service.
 *
 * Each stage is an invokable class (or closure) that receives the DTO and
 * a $next callable, then returns the (possibly mutated) result.
 */
final class OrderProcessingPipeline implements PipelineInterface
{
    public function __construct(
        private readonly Pipeline $pipeline
    ) {}

    public function process(mixed $payload): mixed
    {
        return $this->pipeline
            ->send($payload)
            ->through([
                ValidateOrderItemsStage::class,
                EnrichWithTenantStage::class,
                ApplyPricingRulesStage::class,
            ])
            ->thenReturn();
    }
}

// ---------------------------------------------------------------------------
// Inline pipeline stage classes
// (Can be extracted to separate files as the codebase grows.)
// ---------------------------------------------------------------------------

/**
 * Stage: ensure all line items carry the required fields.
 *
 * @internal
 */
final class ValidateOrderItemsStage
{
    public function __invoke(OrderDTO $dto, \Closure $next): mixed
    {
        foreach ($dto->items as $index => $item) {
            if (empty($item['product_id'])) {
                throw new \InvalidArgumentException("Item #{$index} is missing product_id.");
            }
            if (!isset($item['quantity']) || (int) $item['quantity'] < 1) {
                throw new \InvalidArgumentException("Item #{$index} must have a quantity >= 1.");
            }
        }

        return $next($dto);
    }
}

/**
 * Stage: stamp the DTO with the current tenant when not already set.
 *
 * @internal
 */
final class EnrichWithTenantStage
{
    public function __invoke(OrderDTO $dto, \Closure $next): mixed
    {
        if ($dto->tenantId === null) {
            $tenantId = app('tenant.manager')->getCurrentTenantId();

            $dto = new OrderDTO(
                customerId:      $dto->customerId,
                customerName:    $dto->customerName,
                customerEmail:   $dto->customerEmail,
                items:           $dto->items,
                currency:        $dto->currency,
                tax:             $dto->tax,
                discount:        $dto->discount,
                shippingAddress: $dto->shippingAddress,
                billingAddress:  $dto->billingAddress,
                notes:           $dto->notes,
                metadata:        $dto->metadata,
                tenantId:        $tenantId,
            );
        }

        return $next($dto);
    }
}

/**
 * Stage: apply tenant-specific pricing rules (discounts, taxes, etc.)
 *
 * @internal
 */
final class ApplyPricingRulesStage
{
    public function __invoke(OrderDTO $dto, \Closure $next): mixed
    {
        // Placeholder for future business-rule engine integration.
        Log::debug('[OrderProcessingPipeline] Pricing rules applied.');

        return $next($dto);
    }
}
