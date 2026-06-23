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

final class CreateTenantPlanService
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
    public function execute(array $payload): Result
    {
        try {
            $slug = $this->rules->normalizeSlug((string) ($payload['slug'] ?? ''));
            if ($this->plans->findBySlug($slug) !== null) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant plan slug already exists.',
                ));
            }

            $price = $this->schema->normalizePrice($payload['price'] ?? null);
            $currencyId = $this->positiveInt($payload['currency_id'] ?? null);
            $this->references->assertPlanPricing($price, $currencyId);

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use (
                $payload,
                $slug,
                $price,
                $currencyId,
            ): DataRecord {
                $record = $this->plans->create([
                    'name' => $this->rules->normalizeName((string) ($payload['name'] ?? '')),
                    'slug' => $slug,
                    'features' => $this->schema->normalizeFeatures($payload['features'] ?? null),
                    'limits' => $this->schema->normalizeLimits($payload['limits'] ?? null),
                    'price' => $price,
                    'currency_id' => $currencyId,
                    'billing_interval' => $this->rules->normalizeBillingInterval(
                        isset($payload['billing_interval'])
                            ? (string) $payload['billing_interval']
                            : null,
                    ),
                    'is_active' => (bool) ($payload['is_active'] ?? true),
                    'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.plan.created',
                    eventCategory: 'administration',
                    sourceModule: 'tenant',
                    subjectType: 'tenant_plan',
                    subjectId: (string) $record->id(),
                    subjectReference: $slug,
                    changes: ['new' => $this->summary($record)],
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return $record;
            });

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.create'],
            ));
        }
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
