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
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationUnitGatewayInterface;
use Throwable;

final class CreateTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainServiceInterface $rules,
        private readonly TenantOrganizationUnitGatewayInterface $organizationUnits,
        private readonly SlugGeneratorInterface $slugger,
        private readonly UuidGeneratorInterface $uuid,
        private readonly FileStorageServiceInterface $files,
        private readonly TransactionManagerInterface $transactions,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
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

            $organizationUnit = $payload['organization_unit'] ?? null;
            if (! is_array($organizationUnit)) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'Root organization unit details are required.',
                ));
            }

            if (isset($payload['logo_tmp_path'])) {
                $logoPath = $this->storeLogo(
                    (string) $payload['logo_tmp_path'],
                    (string) ($payload['logo_original_name'] ?? 'logo.bin'),
                    $slug,
                );
            }

            $record = $this->transactions->runInTransaction(function () use (
                $payload,
                $code,
                $name,
                $slug,
                $logoPath,
                $organizationUnit,
            ) {
                $tenant = $this->tenants->create([
                    'uuid' => $this->uuid->generate(),
                    'code' => $code,
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'cross_org_transactions' => (bool) ($payload['cross_org_transactions'] ?? false),
                    'tenant_plan_id' => $this->positiveInt($payload['tenant_plan_id'] ?? null),
                    'base_currency_id' => $this->positiveInt($payload['base_currency_id'] ?? null),
                    'status' => TenantStatus::DRAFT,
                    'trial_ends_at' => $this->dateTime($payload['trial_ends_at'] ?? null),
                    'subscription_ends_at' => $this->dateTime($payload['subscription_ends_at'] ?? null),
                    'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $root = $this->organizationUnits->provisionRoot(
                    (int) $tenant->id(),
                    $organizationUnit,
                );

                $this->audit->record(new AuditEventData(
                    eventName: 'tenant.created',
                    eventCategory: 'administration',
                    sourceModule: 'tenant',
                    subjectType: 'tenant',
                    subjectId: (string) $tenant->id(),
                    subjectReference: $code,
                    changes: ['new' => [
                        'code' => $code,
                        'name' => $name,
                        'status' => TenantStatus::DRAFT,
                        'root_organization_unit' => [
                            'name' => $root->get('name'),
                            'code' => $root->get('code'),
                        ],
                    ]],
                    tags: ['tenant', 'platform', 'onboarding'],
                ));

                return $tenant;
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

        return $value === '' ? null : (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
    }
}
