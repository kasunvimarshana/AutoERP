<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeImmutable;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\SlugGeneratorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Throwable;

final class CreateTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantValueNormalizerInterface $rules,
        private readonly TenantReferenceValidator $references,
        private readonly SlugGeneratorInterface $slugger,
        private readonly UuidGeneratorInterface $uuid,
        private readonly FileStorageServiceInterface $files,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): Result
    {
        $logoPath = null;

        try {
            $code = $this->rules->normalizeCode((string) ($payload['code'] ?? ''));
            $name = $this->rules->normalizeName((string) ($payload['name'] ?? ''));
            $slug = $this->rules->normalizeSlug($this->slugger->generate(
                isset($payload['slug']) ? (string) $payload['slug'] : null,
                $name,
                $code,
            ));

            if ($this->tenants->findByCode($code) !== null) {
                return Result::failure(new Error(
                    TenantErrorCode::DUPLICATE_CODE,
                    'Tenant code already exists.',
                ));
            }
            if ($this->tenants->findBySlug($slug) !== null) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant slug already exists.',
                ));
            }

            $planId = $this->positiveInt($payload['tenant_plan_id'] ?? null);
            $baseCurrencyId = $this->positiveInt($payload['base_currency_id'] ?? null);
            $trialEndsAt = $this->dateTime($payload['trial_ends_at'] ?? null);
            $subscriptionEndsAt = $this->dateTime($payload['subscription_ends_at'] ?? null);
            $this->references->assertActivePlan($planId);
            $this->references->assertActiveCurrency($baseCurrencyId);
            $this->references->assertPeriod($trialEndsAt, $subscriptionEndsAt);

            if (isset($payload['logo_tmp_path'])) {
                $logoPath = $this->storeLogo(
                    (string) $payload['logo_tmp_path'],
                    (string) ($payload['logo_original_name'] ?? 'logo.bin'),
                    $slug,
                );
            }

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use (
                $payload,
                $code,
                $name,
                $slug,
                $logoPath,
                $planId,
                $baseCurrencyId,
                $trialEndsAt,
                $subscriptionEndsAt,
            ): DataRecord {
                $record = $this->tenants->create([
                    'uuid' => $this->uuid->generate(),
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'cross_org_transactions' => (bool) ($payload['cross_org_transactions'] ?? false),
                    'tenant_plan_id' => $planId,
                    'base_currency_id' => $baseCurrencyId,
                    'status' => TenantStatus::DRAFT,
                    'trial_ends_at' => $trialEndsAt,
                    'subscription_ends_at' => $subscriptionEndsAt,
                    'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.created',
                    eventCategory: 'administration',
                    sourceModule: 'tenant',
                    subjectType: 'tenant',
                    subjectId: (string) $record->id(),
                    subjectReference: $code,
                    changes: [
                        'new' => [
                            'code' => $code,
                            'name' => $name,
                            'status' => TenantStatus::DRAFT,
                        ],
                    ],
                    tags: ['tenant', 'platform'],
                ), (int) $record->id());

                return $record;
            });

            return Result::success($record);
        } catch (Throwable $exception) {
            if ($logoPath !== null && $this->files->exists($logoPath)) {
                $this->files->delete($logoPath);
            }

            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.create'],
            ));
        }
    }

    private function storeLogo(string $tmpPath, string $originalName, string $slug): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'bin';

        return $this->files->store(
            $tmpPath,
            'tenants/logos',
            sprintf('%s-%s.%s', $slug, $this->uuid->generate(), $extension),
        );
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function dateTime(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === ''
            ? null
            : (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
    }
}
