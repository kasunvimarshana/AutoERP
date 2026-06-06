<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeImmutable;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\SlugGeneratorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\DTOs\TenantMutationData;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Throwable;

final class CreateTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainServiceInterface $domain,
        private readonly TenantRecordMapperInterface $mapper,
        private readonly SlugGeneratorInterface $slugger,
        private readonly UuidGeneratorInterface $uuidGenerator,
        private readonly FileStorageServiceInterface $files,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $input = TenantMutationData::fromArray($payload);
                $code = $this->domain->normalizeCode($input->code);
                $name = $this->domain->normalizeName($input->name);

                if ($this->tenants->findByCode($code) !== null) {
                    return Result::failure(new Error(TenantErrorCode::DUPLICATE_CODE, 'Tenant code already exists.'));
                }

                $slug = $this->slugger->generate($input->slug, $name, $code);
                $status = $this->domain->normalizeStatus($input->status);
                $isIsolated = $input->isIsolated;
                $isolationKey = $this->domain->ensureIsolationKey(
                    $isIsolated,
                    $input->isolationKey,
                    sprintf('tenant:%s', $slug),
                );

                if ($isolationKey !== null && $this->tenants->findByIsolationKey($isolationKey) !== null) {
                    return Result::failure(
                        new Error(TenantErrorCode::DUPLICATE_ISOLATION_KEY, 'Tenant isolation key already exists.'),
                    );
                }

                $logoPath = $input->logoPath;
                if ($input->logoTmpPath !== null) {
                    $logoPath = $this->storeLogo($input->logoTmpPath, $input->logoOriginalName, $slug);
                }

                $record = $this->tenants->create([
                    'uuid' => $this->uuidGenerator->generate(),
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $this->domain->normalizeOptionalText($logoPath),
                    'cross_org_transactions' => $input->crossOrgTransactions,
                    'tenant_plan_id' => $input->tenantPlanId,
                    'currency_id' => $input->currencyId,
                    'status' => $status,
                    'trial_ends_at' => $this->normalizeNullableDateTime($input->trialEndsAt),
                    'subscription_ends_at' => $this->normalizeNullableDateTime($input->subscriptionEndsAt),
                    'is_active' => $this->domain->deriveActiveFlag($status),
                    'is_isolated' => $isIsolated,
                    'isolation_key' => $isolationKey,
                    'configuration_scope' => $this->domain->normalizeOptionalText($input->configurationScope)
                        ?? strtolower($code),
                    'metadata' => $this->domain->normalizeMetadata($input->metadata),
                    'row_version' => 1,
                ]);

                return Result::success($this->mapper->toValueData($record));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.create'],
            ));
        }
    }

    private function storeLogo(string $tmpPath, ?string $originalName, string $slug): string
    {
        $filename = sprintf(
            '%s-%s.%s',
            $slug,
            $this->uuidGenerator->generate(),
            $this->detectExtension($originalName),
        );

        return $this->files->store($tmpPath, 'tenants/logos', $filename);
    }

    private function detectExtension(?string $originalName): string
    {
        $name = trim((string) $originalName);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $extension = strtolower(trim($extension));

        return $extension === '' ? 'bin' : $extension;
    }

    private function normalizeNullableDateTime(?string $value): ?string
    {
        $candidate = $this->domain->normalizeOptionalText($value);
        if ($candidate === null) {
            return null;
        }

        return (new DateTimeImmutable($candidate))->format('Y-m-d H:i:s');
    }
}
