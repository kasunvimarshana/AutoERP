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
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class UpdateTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainServiceInterface $rules,
        private readonly SlugGeneratorInterface $slugger,
        private readonly UuidGeneratorInterface $uuid,
        private readonly FileStorageServiceInterface $files,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
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
                return Result::failure(new Error(
                    TenantErrorCode::NOT_FOUND,
                    'Tenant not found.',
                ));
            }

            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'The current tenant version is required.',
                ));
            }

            [$code, $name, $slug] = $this->identity($existing, $payload);
            $identityError = $this->validateIdentity($existing, $code, $slug);
            if ($identityError !== null) {
                return Result::failure($identityError);
            }

            $baseCurrencyId = array_key_exists('base_currency_id', $payload)
                ? $this->positiveInt($payload['base_currency_id'])
                : $this->positiveInt($existing->get('base_currency_id'));

            if (
                $existing->get('activated_at') !== null
                && $baseCurrencyId !== $this->positiveInt($existing->get('base_currency_id'))
            ) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'Base accounting currency cannot be changed after activation.',
                ));
            }

            $logoPath = $existing->get('logo_path');
            if (isset($payload['logo_tmp_path'])) {
                $newLogoPath = $this->storeLogo((string) $payload['logo_tmp_path'], $slug);
                $logoPath = $newLogoPath;
            }

            $updated = $this->tenants->updateWithVersion(
                $id,
                $expectedVersion,
                $this->attributes(
                    payload: $payload,
                    existing: $existing,
                    code: $code,
                    name: $name,
                    slug: $slug,
                    logoPath: is_string($logoPath) ? $logoPath : null,
                    baseCurrencyId: $baseCurrencyId,
                ),
            );

            if ($updated === null) {
                $this->removeLogo($newLogoPath, 'version conflict cleanup');

                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant changed since it was loaded. Refresh and try again.',
                ));
            }

            $oldLogoPath = $existing->get('logo_path');
            if (
                $newLogoPath !== null
                && is_string($oldLogoPath)
                && $oldLogoPath !== ''
                && $oldLogoPath !== $newLogoPath
            ) {
                $this->removeLogo($oldLogoPath, 'replaced logo cleanup');
            }

            $this->audit->record(new AuditEventData(
                eventName: 'tenant.updated',
                eventCategory: 'administration',
                sourceModule: 'tenant',
                subjectType: 'tenant',
                subjectId: (string) $updated->id(),
                subjectReference: $code,
                changes: [
                    'old' => $this->summary($existing),
                    'new' => $this->summary($updated),
                ],
                tags: ['tenant', 'platform'],
            ));

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

    /**
     * @param array<string, mixed> $payload
     * @return array{0: string, 1: string, 2: string}
     */
    private function identity(DataRecord $existing, array $payload): array
    {
        $code = array_key_exists('code', $payload)
            ? $this->rules->normalizeCode((string) $payload['code'])
            : (string) $existing->require('code');
        $name = array_key_exists('name', $payload)
            ? $this->rules->normalizeName((string) $payload['name'])
            : (string) $existing->require('name');
        $slug = array_key_exists('slug', $payload)
            ? $this->rules->normalizeSlug(
                $this->slugger->generate((string) $payload['slug'], $name, $code),
            )
            : (string) $existing->require('slug');

        return [$code, $name, $slug];
    }

    private function validateIdentity(DataRecord $existing, string $code, string $slug): ?Error
    {
        $status = (string) $existing->require('status');
        if (
            $status !== TenantStatus::DRAFT
            && ($code !== $existing->get('code') || $slug !== $existing->get('slug'))
        ) {
            return new Error(
                TenantErrorCode::INVALID_VALUE,
                'Tenant code and slug are immutable after activation.',
            );
        }

        $duplicate = $this->tenants->findByCode($code);
        if ($duplicate !== null && (int) $duplicate->id() !== (int) $existing->id()) {
            return new Error(
                TenantErrorCode::DUPLICATE_CODE,
                'Tenant code already exists.',
            );
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function attributes(
        array $payload,
        DataRecord $existing,
        string $code,
        string $name,
        string $slug,
        ?string $logoPath,
        ?int $baseCurrencyId,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'logo_path' => $logoPath,
            'cross_org_transactions' => array_key_exists('cross_org_transactions', $payload)
                ? (bool) $payload['cross_org_transactions']
                : (bool) $existing->get('cross_org_transactions'),
            'tenant_plan_id' => array_key_exists('tenant_plan_id', $payload)
                ? $this->positiveInt($payload['tenant_plan_id'])
                : $existing->get('tenant_plan_id'),
            'base_currency_id' => $baseCurrencyId,
            'trial_ends_at' => array_key_exists('trial_ends_at', $payload)
                ? $this->dateTime($payload['trial_ends_at'])
                : $existing->get('trial_ends_at'),
            'subscription_ends_at' => array_key_exists('subscription_ends_at', $payload)
                ? $this->dateTime($payload['subscription_ends_at'])
                : $existing->get('subscription_ends_at'),
            'metadata' => $this->rules->normalizeMetadata($existing->get('metadata')),
            'updated_by' => $this->currentUser->currentUserId(),
        ];
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
            $this->logger->warning('Tenant logo cleanup failed.', [
                'path' => $path,
                'reason' => $reason,
            ]);
        }
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

    /** @return array<string, mixed> */
    private function summary(DataRecord $record): array
    {
        return [
            'name' => $record->get('name'),
            'tenant_plan_id' => $record->get('tenant_plan_id'),
            'base_currency_id' => $record->get('base_currency_id'),
            'cross_org_transactions' => $record->get('cross_org_transactions'),
        ];
    }
}
