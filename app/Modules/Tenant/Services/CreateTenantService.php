<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\SlugGeneratorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantOnboardingStateModel;
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
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly TenantOnboardingStateModel $onboardingStates,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): Result
    {
        try {
            $code = $this->rules->normalizeCode((string) ($payload['code'] ?? ''));
            $name = $this->rules->normalizeName((string) ($payload['name'] ?? ''));
            $slug = $this->rules->normalizeSlug($this->slugger->generate(
                isset($payload['slug']) ? (string) $payload['slug'] : null,
                $name,
                $code,
            ));

            if ($this->tenants->findByCode($code) !== null) {
                return Result::failure(new Error(TenantErrorCode::DUPLICATE_CODE, 'Tenant code already exists.'));
            }
            if ($this->tenants->findBySlug($slug) !== null) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant slug already exists.'));
            }

            $baseCurrencyId = $this->positiveInt($payload['base_currency_id'] ?? null);
            $this->references->assertActiveCurrency($baseCurrencyId);

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use (
                $code,
                $name,
                $slug,
                $baseCurrencyId,
            ): DataRecord {
                $record = $this->tenants->create([
                    'uuid' => $this->uuid->generate(),
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_object_key' => null,
                    'logo_mime_type' => null,
                    'logo_size_bytes' => null,
                    'base_currency_id' => $baseCurrencyId,
                    'status' => TenantStatus::DRAFT,
                    'status_changed_at' => now(),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $tenantId = (int) $record->id();
                $this->executionContext->runForTenant($tenantId, function () use ($tenantId): void {
                    $this->onboardingStates->newQuery()->create([
                        'tenant_id' => $tenantId,
                        'status' => TenantOnboardingStatus::PENDING,
                        'row_version' => 1,
                        'created_by' => $this->currentUser->currentUserId(),
                        'updated_by' => $this->currentUser->currentUserId(),
                    ]);
                });

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.created',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant',
                    subjectId: (string) $record->id(),
                    subjectReference: $code,
                    changes: ['new' => [
                        'code' => $code,
                        'name' => $name,
                        'status' => TenantStatus::DRAFT,
                        'base_currency_id' => $baseCurrencyId,
                    ]],
                    tags: ['tenant', 'platform'],
                ), $tenantId);

                return $record;
            });

            return Result::success($this->tenants->findById($record->id()) ?? $record);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.create'],
            ));
        }
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
