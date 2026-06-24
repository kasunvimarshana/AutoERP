<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Audit\Constants\AuditEventCategory;
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
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\TenantReferenceValidator;
use Throwable;

final class CreateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantPlanRevisionRepositoryInterface $revisions,
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
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan slug already exists.'));
            }

            $revision = $this->revisionAttributes($payload);
            $this->references->assertPlanPricing(
                (string) $revision['price'],
                is_int($revision['currency_id']) ? $revision['currency_id'] : null,
            );

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use ($payload, $slug, $revision): DataRecord {
                $plan = $this->plans->create([
                    'name' => $this->rules->normalizeName((string) ($payload['name'] ?? '')),
                    'slug' => $slug,
                    'is_active' => true,
                    'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $createdRevision = $this->revisions->createNext($plan->id(), $revision);
                $complete = $this->plans->findById($plan->id()) ?? $plan;

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.plan.created',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant_plan',
                    subjectId: (string) $plan->id(),
                    subjectReference: $slug,
                    changes: ['new' => [
                        'name' => $complete->get('name'),
                        'slug' => $slug,
                        'revision' => $createdRevision->toArray(),
                        'is_active' => $complete->get('is_active'),
                    ]],
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return $complete;
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

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function revisionAttributes(array $payload): array
    {
        return [
            'features' => $this->schema->normalizeFeatures($payload['features'] ?? null),
            'limits' => $this->schema->normalizeLimits($payload['limits'] ?? null),
            'price' => $this->schema->normalizePrice($payload['price'] ?? null),
            'currency_id' => $this->positiveInt($payload['currency_id'] ?? null),
            'billing_interval' => $this->rules->normalizeBillingInterval(
                isset($payload['billing_interval']) ? (string) $payload['billing_interval'] : null,
            ),
            'effective_at' => isset($payload['effective_at'])
                ? new \DateTimeImmutable((string) $payload['effective_at'])
                : now(),
            'created_by' => $this->currentUser->currentUserId(),
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
