<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use DateTimeImmutable;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\DTOs\TenantMutationData;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Throwable;

final class UpdateTenantService
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
    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->tenants->findById($id);

                if ($existing === null) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
                }

                $input = TenantMutationData::fromArray(array_merge($existing->toArray(), $payload));
                $code = $this->domain->normalizeCode($input->code);
                $name = $this->domain->normalizeName($input->name);
                $slug = $this->slugger->generate($input->slug, $name, $code);
                $status = $this->domain->normalizeStatus($input->status);

                $byCode = $this->tenants->findByCode($code);
                if ($byCode !== null && (string) $byCode->id() !== (string) $existing->id()) {
                    return Result::failure(new Error(TenantErrorCode::DUPLICATE_CODE, 'Tenant code already exists.'));
                }

                $isolationKey = $this->domain->ensureIsolationKey(
                    $input->isIsolated,
                    $input->isolationKey,
                    sprintf('tenant:%s', $slug),
                );

                if ($isolationKey !== null) {
                    $byIsolationKey = $this->tenants->findByIsolationKey($isolationKey);
                    if ($byIsolationKey !== null && (string) $byIsolationKey->id() !== (string) $existing->id()) {
                        return Result::failure(
                            new Error(TenantErrorCode::DUPLICATE_ISOLATION_KEY, 'Tenant isolation key already exists.'),
                        );
                    }
                }

                $logoPath = $input->logoPath;
                if ($input->logoTmpPath !== null) {
                    $logoPath = $this->storeLogo($input->logoTmpPath, $input->logoOriginalName, $slug);
                }

                $record = $this->tenants->update($id, [
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
                    'is_isolated' => $input->isIsolated,
                    'isolation_key' => $isolationKey,
                    'configuration_scope' => $this->domain->normalizeOptionalText($input->configurationScope),
                    'metadata' => $this->domain->normalizeMetadata($input->metadata),
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return Result::success($this->mapper->toValueData($record));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.update', 'tenant_id' => (string) $id],
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
