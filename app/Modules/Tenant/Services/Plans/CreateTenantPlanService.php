<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use DateTimeImmutable;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
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
        private readonly ClockInterface $clock,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): Result
    {
        try {
            $slug = $this->rules->normalizeSlug((string) ($payload['slug'] ?? ''));
            $revision = $this->revisionAttributes($payload);
            $this->references->assertPlanPricing(
                (string) $revision['price'],
                is_int($revision['currency_id']) ? $revision['currency_id'] : null,
            );

            /** @var array{status:string,record?:DataRecord} $outcome */
            $outcome = $this->transactions->runInTransaction(function () use ($payload, $slug, $revision): array {
                if ($this->plans->findBySlug($slug) !== null) {
                    return ['status' => 'duplicate'];
                }

                $plan = $this->plans->create([
                    'name' => $this->rules->normalizeName((string) ($payload['name'] ?? '')),
                    'slug' => $slug,
                    'is_active' => true,
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

                return ['status' => 'success', 'record' => $complete];
            });

            return match ($outcome['status']) {
                'success' => Result::success($outcome['record']),
                default => Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan slug already exists.')),
            };
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
        $effectiveAt = isset($payload['effective_at'])
            ? new DateTimeImmutable((string) $payload['effective_at'])
            : $this->clock->now();

        return [
            'features' => $this->schema->normalizeFeatures($payload['features'] ?? null),
            'limits' => $this->schema->normalizeLimits($payload['limits'] ?? null),
            'price' => $this->schema->normalizePrice($payload['price'] ?? null),
            'currency_id' => $this->positiveInt($payload['currency_id'] ?? null),
            'billing_interval' => $this->rules->normalizeBillingInterval(
                isset($payload['billing_interval']) ? (string) $payload['billing_interval'] : null,
            ),
            'effective_at' => $effectiveAt,
            'change_note' => $this->changeNote($payload['change_note'] ?? null),
            'created_by' => $this->currentUser->currentUserId(),
            'created_at' => $this->clock->now(),
        ];
    }


    private function changeNote(mixed $value): string
    {
        $note = is_scalar($value) ? trim((string) $value) : '';
        if (mb_strlen($note) < 5) {
            throw new \InvalidArgumentException('A meaningful plan revision note is required.');
        }

        return $note;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
