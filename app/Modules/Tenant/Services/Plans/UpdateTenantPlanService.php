<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use DateTimeImmutable;
use DateTimeInterface;
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

final class UpdateTenantPlanService
{
    private const REVISION_FIELDS = [
        'features', 'limits', 'price', 'currency_id', 'billing_interval', 'effective_at',
    ];

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
    public function execute(int|string $id, array $payload): Result
    {
        try {
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'The current tenant plan version is required.',
                ));
            }

            /** @var array{status:string,record?:DataRecord} $outcome */
            $outcome = $this->transactions->runInTransaction(function () use ($id, $payload, $expectedVersion): array {
                $existing = $this->plans->findById($id, true);
                if ($existing === null) {
                    return ['status' => 'not_found'];
                }
                if ((int) $existing->require('row_version') !== $expectedVersion) {
                    return ['status' => 'version_conflict'];
                }

                $slug = array_key_exists('slug', $payload)
                    ? $this->rules->normalizeSlug((string) $payload['slug'])
                    : (string) $existing->require('slug');
                $duplicate = $this->plans->findBySlug($slug);
                if ($duplicate !== null && (int) $duplicate->id() !== (int) $existing->id()) {
                    return ['status' => 'duplicate'];
                }

                $latest = $this->revisions->findLatestByPlan($id);
                if ($latest === null) {
                    return ['status' => 'missing_revision'];
                }

                $revision = $this->revisionAttributes($payload, $latest);
                $createRevision = $this->hasRevisionInput($payload)
                    && $this->revisionChanged($latest, $revision, $payload);
                if ($createRevision) {
                    $revision['change_note'] = $this->changeNote($payload['change_note'] ?? null);
                    $this->references->assertPlanPricing(
                        (string) $revision['price'],
                        is_int($revision['currency_id']) ? $revision['currency_id'] : null,
                    );
                }

                $updated = $this->plans->updateWithVersion($id, $expectedVersion, [
                    'name' => array_key_exists('name', $payload)
                        ? $this->rules->normalizeName((string) $payload['name'])
                        : $existing->get('name'),
                    'slug' => $slug,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return ['status' => 'version_conflict'];
                }

                $newRevision = $createRevision
                    ? $this->revisions->createNext($id, $revision)
                    : null;
                $updated = $this->plans->findById($id) ?? $updated;

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.plan.updated',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant_plan',
                    subjectId: (string) $updated->id(),
                    subjectReference: $slug,
                    changes: [
                        'old' => $existing->toArray(),
                        'new' => $updated->toArray(),
                    ],
                    metadata: ['revision_created' => $newRevision?->get('revision_number')],
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return ['status' => 'success', 'record' => $updated];
            });

            return match ($outcome['status']) {
                'success' => Result::success($outcome['record']),
                'not_found' => Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.')),
                'duplicate' => Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan slug already exists.')),
                'missing_revision' => Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant plan has no immutable revision.')),
                default => Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                )),
            };
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.update', 'plan_id' => (string) $id],
            ));
        }
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function revisionAttributes(array $payload, DataRecord $latest): array
    {
        return [
            'features_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'features' => array_key_exists('features', $payload)
                ? $this->schema->normalizeFeatures($payload['features'])
                : $this->schema->normalizePersistedFeatures($latest->get('features')),
            'limits_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'limits' => array_key_exists('limits', $payload)
                ? $this->schema->normalizeLimits($payload['limits'])
                : $this->schema->normalizeLimits($latest->get('limits')),
            'price' => array_key_exists('price', $payload)
                ? $this->schema->normalizePrice($payload['price'])
                : $this->schema->normalizePrice($latest->get('price')),
            'currency_id' => array_key_exists('currency_id', $payload)
                ? $this->positiveInt($payload['currency_id'])
                : $this->positiveInt($latest->get('currency_id')),
            'billing_interval' => array_key_exists('billing_interval', $payload)
                ? $this->rules->normalizeBillingInterval((string) $payload['billing_interval'])
                : (string) $latest->require('billing_interval'),
            'effective_at' => array_key_exists('effective_at', $payload)
                ? $this->dateTime($payload['effective_at'])
                : $this->clock->now(),
            'change_note' => (string) ($latest->get('change_note') ?? ''),
            'created_by' => $this->currentUser->currentUserId(),
            'created_at' => $this->clock->now(),
        ];
    }

    /** @param array<string, mixed> $revision @param array<string, mixed> $payload */
    private function revisionChanged(DataRecord $latest, array $revision, array $payload): bool
    {
        return $this->schema->normalizePersistedFeatures($latest->get('features')) !== $revision['features']
            || $this->schema->normalizeLimits($latest->get('limits')) !== $revision['limits']
            || $this->schema->normalizePrice($latest->get('price')) !== $revision['price']
            || $this->positiveInt($latest->get('currency_id')) !== $revision['currency_id']
            || (string) $latest->get('billing_interval') !== $revision['billing_interval']
            || (
                array_key_exists('effective_at', $payload)
                && $this->dateTime($latest->get('effective_at'))->format('Y-m-d H:i:s')
                    !== $this->dateTime($revision['effective_at'])->format('Y-m-d H:i:s')
            );
    }

    /** @param array<string, mixed> $payload */
    private function hasRevisionInput(array $payload): bool
    {
        return array_intersect(self::REVISION_FIELDS, array_keys($payload)) !== [];
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

    private function dateTime(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            throw new \InvalidArgumentException('Plan revision effective date is required.');
        }

        return new DateTimeImmutable($value);
    }
}
