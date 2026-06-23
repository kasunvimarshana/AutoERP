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

            $status = (string) $existing->require('status');
            $planId = array_key_exists('tenant_plan_id', $payload)
                ? $this->positiveInt($payload['tenant_plan_id'])
                : $this->positiveInt($existing->get('tenant_plan_id'));
            $baseCurrencyId = array_key_exists('base_currency_id', $payload)
                ? $this->positiveInt($payload['base_currency_id'])
                : $this->positiveInt($existing->get('base_currency_id'));
            $trialEndsAt = array_key_exists('trial_ends_at', $payload)
                ? $this->dateTime($payload['trial_ends_at'])
                : $this->nullableDateTime($existing->get('trial_ends_at'));
            $subscriptionEndsAt = array_key_exists('subscription_ends_at', $payload)
                ? $this->dateTime($payload['subscription_ends_at'])
                : $this->nullableDateTime($existing->get('subscription_ends_at'));

            if (
                $status !== TenantStatus::DRAFT
                && $baseCurrencyId !== $this->positiveInt($existing->get('base_currency_id'))
            ) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'Base accounting currency can only be changed while the tenant is in draft status.',
                ));
            }
            if ($status === TenantStatus::ACTIVE && $planId === null) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'An active tenant must remain assigned to an active plan.',
                ));
            }

            $this->references->assertActivePlan($planId);
            $this->references->assertActiveCurrency($baseCurrencyId);
            $this->references->assertPeriod($trialEndsAt, $subscriptionEndsAt);

            $logoPath = $existing->get('logo_path');
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
                $planId,
                $baseCurrencyId,
                $trialEndsAt,
                $subscriptionEndsAt,
            ): ?DataRecord {
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
                        planId: $planId,
                        baseCurrencyId: $baseCurrencyId,
                        trialEndsAt: $trialEndsAt,
                        subscriptionEndsAt: $subscriptionEndsAt,
                    ),
                );

                if ($updated === null) {
                    return null;
                }

                $this->audit->recordPlatform(new AuditEventData(
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

            $oldLogoPath = $existing->get('logo_path');
            if (
                $newLogoPath !== null
                && is_string($oldLogoPath)
                && $oldLogoPath !== ''
                && $oldLogoPath !== $newLogoPath
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

    /**
     * @param array<string, mixed> $payload
     * @return array{0:string,1:string,2:string}
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
        ?int $planId,
        ?int $baseCurrencyId,
        ?string $trialEndsAt,
        ?string $subscriptionEndsAt,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'logo_path' => $logoPath,
            'cross_org_transactions' => array_key_exists('cross_org_transactions', $payload)
                ? (bool) $payload['cross_org_transactions']
                : (bool) $existing->get('cross_org_transactions'),
            'tenant_plan_id' => $planId,
            'base_currency_id' => $baseCurrencyId,
            'trial_ends_at' => $trialEndsAt,
            'subscription_ends_at' => $subscriptionEndsAt,
            'metadata' => array_key_exists('metadata', $payload)
                ? $this->rules->normalizeMetadata($payload['metadata'])
                : $this->rules->normalizeMetadata($existing->get('metadata')),
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

    private function nullableDateTime(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === ''
            ? null
            : (new DateTimeImmutable((string) $value))->format('Y-m-d H:i:s');
    }

    /** @return array<string, mixed> */
    private function summary(DataRecord $record): array
    {
        return [
            'code' => $record->get('code'),
            'name' => $record->get('name'),
            'slug' => $record->get('slug'),
            'tenant_plan_id' => $record->get('tenant_plan_id'),
            'base_currency_id' => $record->get('base_currency_id'),
            'trial_ends_at' => $record->get('trial_ends_at'),
            'subscription_ends_at' => $record->get('subscription_ends_at'),
            'cross_org_transactions' => $record->get('cross_org_transactions'),
        ];
    }
}
