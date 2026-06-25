<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\SlugGeneratorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\Storage\TenantLogoStorageService;
use Throwable;

final class UpdateTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantValueNormalizerInterface $rules,
        private readonly TenantReferenceValidator $references,
        private readonly SlugGeneratorInterface $slugger,
        private readonly TenantLogoStorageService $logos,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(int|string $id, array $payload): Result
    {
        $newLogoPath = null;

        try {
            $existing = $this->tenants->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $tenantId = (int) $existing->id();
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(TenantErrorCode::VERSION_CONFLICT, 'The current tenant version is required.'));
            }

            [$code, $name, $slug] = $this->identity($existing, $payload);
            $identityError = $this->validateIdentity($existing, $code, $slug);
            if ($identityError !== null) {
                return Result::failure($identityError);
            }

            $baseCurrencyId = array_key_exists('base_currency_id', $payload)
                ? $this->positiveInt($payload['base_currency_id'])
                : $this->positiveInt($existing->get('base_currency_id'));
            $existingBaseCurrencyId = $this->positiveInt($existing->get('base_currency_id'));
            if ($existing->get('status') !== TenantStatus::DRAFT && $baseCurrencyId !== $existingBaseCurrencyId) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'Base accounting currency can only be changed while the tenant is in draft status.',
                ));
            }
            if ($baseCurrencyId !== $existingBaseCurrencyId) {
                $this->references->assertActiveCurrency($baseCurrencyId);
            }

            $oldLogoPath = is_string($existing->get('logo_path'))
                ? trim((string) $existing->get('logo_path')) ?: null
                : null;
            $removeLogo = (bool) ($payload['remove_logo'] ?? false);
            $logoPath = $removeLogo ? null : $oldLogoPath;
            if (isset($payload['logo_tmp_path'])) {
                $newLogoPath = $this->logos->store(
                    $tenantId,
                    (string) $payload['logo_tmp_path'],
                    (string) ($payload['logo_original_name'] ?? 'logo.bin'),
                );
                $logoPath = $newLogoPath;
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $existing,
                $code,
                $name,
                $slug,
                $logoPath,
                $oldLogoPath,
                $baseCurrencyId,
            ): ?DataRecord {
                $updated = $this->tenants->updateWithVersion($id, $expectedVersion, [
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'base_currency_id' => $baseCurrencyId,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
                }

                if ($oldLogoPath !== null && $oldLogoPath !== $logoPath) {
                    $this->logos->scheduleCleanup($tenantId, $oldLogoPath, 'tenant logo replacement cleanup');
                }

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.updated',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant',
                    subjectId: (string) $updated->id(),
                    subjectReference: $code,
                    changes: [
                        'old' => $this->summary($existing),
                        'new' => $this->summary($updated),
                    ],
                    tags: ['tenant', 'platform'],
                ), $tenantId);

                return $updated;
            });

            if ($updated === null) {
                $this->cleanupNewLogo($tenantId, $newLogoPath, 'tenant logo version conflict cleanup');

                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant changed since it was loaded. Refresh and try again.',
                ));
            }

            if ($oldLogoPath !== null && $oldLogoPath !== $logoPath) {
                $this->logos->processCleanup($tenantId, $oldLogoPath);
            }

            return Result::success($updated);
        } catch (Throwable $exception) {
            if (isset($tenantId)) {
                $this->cleanupNewLogo($tenantId, $newLogoPath, 'failed tenant logo update cleanup');
            }

            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.update', 'tenant_id' => (string) $id],
            ));
        }
    }

    /** @param array<string, mixed> $payload @return array{0:string,1:string,2:string} */
    private function identity(DataRecord $existing, array $payload): array
    {
        $code = array_key_exists('code', $payload)
            ? $this->rules->normalizeCode((string) $payload['code'])
            : (string) $existing->require('code');
        $name = array_key_exists('name', $payload)
            ? $this->rules->normalizeName((string) $payload['name'])
            : (string) $existing->require('name');
        $slug = array_key_exists('slug', $payload)
            ? $this->rules->normalizeSlug($this->slugger->generate((string) $payload['slug'], $name, $code))
            : (string) $existing->require('slug');

        return [$code, $name, $slug];
    }

    private function validateIdentity(DataRecord $existing, string $code, string $slug): ?Error
    {
        if (
            $existing->get('status') !== TenantStatus::DRAFT
            && ($code !== $existing->get('code') || $slug !== $existing->get('slug'))
        ) {
            return new Error(
                TenantErrorCode::INVALID_VALUE,
                'Tenant code and slug can only be changed while the tenant is in draft status.',
            );
        }

        $duplicateCode = $this->tenants->findByCode($code);
        if ($duplicateCode !== null && (int) $duplicateCode->id() !== (int) $existing->id()) {
            return new Error(TenantErrorCode::DUPLICATE_CODE, 'Tenant code already exists.');
        }

        $duplicateSlug = $this->tenants->findBySlug($slug);
        if ($duplicateSlug !== null && (int) $duplicateSlug->id() !== (int) $existing->id()) {
            return new Error(TenantErrorCode::CONFLICT, 'Tenant slug already exists.');
        }

        return null;
    }

    private function cleanupNewLogo(int $tenantId, ?string $path, string $reason): void
    {
        if ($path === null) {
            return;
        }

        $this->logos->scheduleCleanup($tenantId, $path, $reason);
        $this->logos->processCleanup($tenantId, $path);
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array<string, mixed> */
    private function summary(DataRecord $record): array
    {
        return [
            'code' => $record->get('code'),
            'name' => $record->get('name'),
            'slug' => $record->get('slug'),
            'base_currency_id' => $record->get('base_currency_id'),
            'has_logo' => is_string($record->get('logo_path')) && trim((string) $record->get('logo_path')) !== '',
        ];
    }
}
