<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\TenantReferenceValidator;
use Throwable;

final class UpdateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantValueNormalizerInterface $rules,
        private readonly TenantPlanSchema $schema,
        private readonly TenantReferenceValidator $references,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->plans->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
            }

            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'The current tenant plan version is required.',
                ));
            }

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

            $attributes = $this->attributes($payload, $existing, $slug);
            if (! (bool) $attributes['is_active'] && $this->plans->isAssigned($id)) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'A tenant plan assigned to tenants cannot be deactivated. Reassign those tenants first.',
                ));
            }
            $this->references->assertPlanPricing(
                (string) $attributes['price'],
                is_numeric($attributes['currency_id']) ? (int) $attributes['currency_id'] : null,
            );

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $expectedVersion,
                $attributes,
                $existing,
                $slug,
            ): ?DataRecord {
                $updated = $this->plans->updateWithVersion($id, $expectedVersion, $attributes);
                if ($updated === null) {
                    return null;
                }

                $this->audit->recordPlatform(new AuditEventData(
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
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return $updated;
            });

            return $updated === null
                ? Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                ))
                : Result::success($updated);
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
            'features' => array_key_exists('features', $payload)
                ? $this->schema->normalizeFeatures($payload['features'])
                : $this->schema->normalizeFeatures($existing->get('features')),
            'limits' => array_key_exists('limits', $payload)
                ? $this->schema->normalizeLimits($payload['limits'])
                : $this->schema->normalizeLimits($existing->get('limits')),
            'price' => array_key_exists('price', $payload)
                ? $this->schema->normalizePrice($payload['price'])
                : $this->schema->normalizePrice($existing->get('price')),
            'currency_id' => array_key_exists('currency_id', $payload)
                ? $this->positiveInt($payload['currency_id'])
                : $existing->get('currency_id'),
            'billing_interval' => array_key_exists('billing_interval', $payload)
                ? $this->rules->normalizeBillingInterval((string) $payload['billing_interval'])
                : $existing->get('billing_interval'),
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : (bool) $existing->get('is_active'),
            'metadata' => array_key_exists('metadata', $payload)
                ? $this->rules->normalizeMetadata($payload['metadata'])
                : $this->rules->normalizeMetadata($existing->get('metadata')),
            'updated_by' => $this->currentUser->currentUserId(),
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array<string, mixed> */
    private function summary(DataRecord $record): array
    {
        return [
            'name' => $record->get('name'),
            'slug' => $record->get('slug'),
            'features' => $record->get('features'),
            'limits' => $record->get('limits'),
            'price' => $record->get('price'),
            'currency_id' => $record->get('currency_id'),
            'billing_interval' => $record->get('billing_interval'),
            'is_active' => $record->get('is_active'),
        ];
    }
}
