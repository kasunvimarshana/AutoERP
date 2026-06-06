<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Throwable;

final class UpdateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantDomainServiceInterface $domain,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->plans->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
            }

            $slug = $this->domain->normalizeSlug((string) ($payload['slug'] ?? $existing->require('slug')));
            $bySlug = $this->plans->findBySlug($slug);
            if ($bySlug !== null && (string) $bySlug->id() !== (string) $existing->id()) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan slug already exists.'));
            }

            $record = $this->plans->update($id, [
                'name' => $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name'))),
                'slug' => $slug,
                'features' => is_array($payload['features'] ?? null)
                    ? $payload['features']
                    : $existing->get('features'),
                'limits' => is_array($payload['limits'] ?? null) ? $payload['limits'] : $existing->get('limits'),
                'price' => array_key_exists('price', $payload) ? (float) $payload['price'] : $existing->get('price'),
                'currency_id' => array_key_exists('currency_id', $payload)
                    ? (isset($payload['currency_id']) ? (int) $payload['currency_id'] : null)
                    : $existing->get('currency_id'),
                'billing_interval' => $this->domain->normalizeBillingInterval(
                    isset($payload['billing_interval'])
                        ? (string) $payload['billing_interval']
                        : (string) $existing->get('billing_interval'),
                ),
                'is_active' => array_key_exists('is_active', $payload)
                    ? (bool) $payload['is_active']
                    : (bool) $existing->get('is_active', true),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata', [])),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
