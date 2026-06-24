<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditEventCategory;
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
use Psr\Log\LoggerInterface;
use Throwable;

final class UpdateTenantService
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
        private readonly LoggerInterface $logger,
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
            if (
                $existing->get('status') !== TenantStatus::DRAFT
                && $baseCurrencyId !== $existingBaseCurrencyId
            ) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'Base accounting currency can only be changed while the tenant is in draft status.',
                ));
            }
            if ($baseCurrencyId !== $existingBaseCurrencyId) {
                $this->references->assertActiveCurrency($baseCurrencyId);
            }

            $oldLogoPath = is_string($existing->get('logo_path')) ? $existing->get('logo_path') : null;
            $removeLogo = (bool) ($payload['remove_logo'] ?? false);
            $logoPath = $removeLogo ? null : $oldLogoPath;
            if (isset($payload['logo_tmp_path'])) {
                $newLogoPath = $this->storeLogo((string) $payload['logo_tmp_path'], $slug);
                $logoPath = $newLogoPath;
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $expectedVersion,
                $payload,
                $existing,
                $code,
                $name,
                $slug,
                $logoPath,
                $baseCurrencyId,
            ): ?DataRecord {
                $updated = $this->tenants->updateWithVersion($id, $expectedVersion, [
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'cross_org_transactions' => array_key_exists('cross_org_transactions', $payload)
                        ? (bool) $payload['cross_org_transactions']
                        : (bool) $existing->get('cross_org_transactions'),
                    'base_currency_id' => $baseCurrencyId,
                    'metadata' => array_key_exists('metadata', $payload)
                        ? $this->rules->normalizeMetadata($payload['metadata'])
                        : $this->rules->normalizeMetadata($existing->get('metadata')),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
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
                ), (int) $updated->id());

                return $updated;
            });

            if ($updated === null) {
                $this->removeLogo($newLogoPath, 'version conflict cleanup');

                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant changed since it was loaded. Refresh and try again.',
                ));
            }

            if (
                is_string($oldLogoPath)
                && $oldLogoPath !== ''
                && $oldLogoPath !== $logoPath
            ) {
                $this->removeLogo($oldLogoPath, 'replaced logo cleanup');
            }

            return Result::success($updated);
        } catch (Throwable $exception) {
            $this->removeLogo($newLogoPath, 'failed update cleanup');

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

    private function storeLogo(string $temporaryPath, string $slug): string
    {
        return $this->files->store(
            $temporaryPath,
            'tenants/logos',
            sprintf('%s-%s', $slug, $this->uuid->generate()),
        );
    }

    private function removeLogo(?string $path, string $reason): void
    {
        if ($path === null || $path === '' || ! $this->files->exists($path)) {
            return;
        }

        if (! $this->files->delete($path)) {
            $this->logger->warning('Tenant logo cleanup failed.', ['path' => $path, 'reason' => $reason]);
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
            'code' => $record->get('code'),
            'name' => $record->get('name'),
            'slug' => $record->get('slug'),
            'base_currency_id' => $record->get('base_currency_id'),
            'cross_org_transactions' => $record->get('cross_org_transactions'),
        ];
    }
}
