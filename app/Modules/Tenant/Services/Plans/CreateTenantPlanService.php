<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Throwable;

final class CreateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantDomainServiceInterface $domain,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $slug = $this->domain->normalizeSlug((string) ($payload['slug'] ?? ''));

            if ($this->plans->findBySlug($slug) !== null) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan slug already exists.'));
            }

            $record = $this->plans->create([
                'name' => $this->domain->normalizeName((string) ($payload['name'] ?? '')),
                'slug' => $slug,
                'features' => is_array($payload['features'] ?? null) ? $payload['features'] : null,
                'limits' => is_array($payload['limits'] ?? null) ? $payload['limits'] : null,
                'price' => isset($payload['price']) ? (float) $payload['price'] : 0,
                'currency_id' => isset($payload['currency_id']) ? (int) $payload['currency_id'] : null,
                'billing_interval' => $this->domain->normalizeBillingInterval(
                    isset($payload['billing_interval']) ? (string) $payload['billing_interval'] : null,
                ),
                'is_active' => isset($payload['is_active']) ? (bool) $payload['is_active'] : true,
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
