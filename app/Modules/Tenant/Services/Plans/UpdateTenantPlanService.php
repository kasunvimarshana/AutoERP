<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\DTOs\DataRecord;
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
        private readonly TenantDomainServiceInterface $rules,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->plans->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(
                    TenantErrorCode::NOT_FOUND,
                    'Tenant plan not found.',
                ));
            }

            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            $slug = array_key_exists('slug', $payload)
                ? $this->rules->normalizeSlug((string) $payload['slug'])
                : (string) $existing->require('slug');

            $duplicate = $this->plans->findBySlug($slug);
            if ($duplicate !== null && (int) $duplicate->id() !== (int) $existing->id()) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant plan slug already exists.',
                ));
            }

            $updated = $this->plans->updateWithVersion(
                $id,
                $expectedVersion,
                $this->attributes($payload, $existing, $slug),
            );
            if ($updated === null) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                ));
            }

            $this->audit->record(new AuditEventData(
                eventName: 'tenant.plan.updated',
                eventCategory: 'administration',
                sourceModule: 'tenant',
                subjectType: 'tenant_plan',
                subjectId: (string) $updated->id(),
                subjectReference: $slug,
                changes: [
                    'old' => $this->summary($existing),
                    'new' => $this->summary($updated),
                ],
                tags: ['tenant', 'plan'],
            ));

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.update', 'plan_id' => (string) $id],
            ));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function attributes(array $payload, DataRecord $existing, string $slug): array
    {
        return [
            'name' => array_key_exists('name', $payload)
                ? $this->rules->normalizeName((string) $payload['name'])
                : $existing->get('name'),
            'slug' => $slug,
            'features' => array_key_exists('features', $payload) && is_array($payload['features'])
                ? $payload['features']
                : $existing->get('features'),
            'limits' => array_key_exists('limits', $payload) && is_array($payload['limits'])
                ? $payload['limits']
                : $existing->get('limits'),
            'price' => array_key_exists('price', $payload)
                ? (string) $payload['price']
                : $existing->get('price'),
            'currency_id' => array_key_exists('currency_id', $payload)
                ? (isset($payload['currency_id']) ? (int) $payload['currency_id'] : null)
                : $existing->get('currency_id'),
            'billing_interval' => array_key_exists('billing_interval', $payload)
                ? $this->rules->normalizeBillingInterval((string) $payload['billing_interval'])
                : $existing->get('billing_interval'),
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : $existing->get('is_active'),
            'metadata' => $existing->get('metadata'),
            'updated_by' => $this->currentUser->currentUserId(),
        ];
    }

    /** @return array<string, mixed> */
    private function summary(DataRecord $record): array
    {
        return [
            'name' => $record->get('name'),
            'price' => $record->get('price'),
            'currency_id' => $record->get('currency_id'),
            'billing_interval' => $record->get('billing_interval'),
            'is_active' => $record->get('is_active'),
        ];
    }
}
