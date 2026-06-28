<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnitDocuments;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Exceptions\OrganizationUnitException;
use Modules\OrganizationUnit\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Services\Audit\OrganizationUnitAuditService;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Services\Storage\OrganizationUnitAssetStorageService;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use Modules\Tenant\Services\Storage\TenantStoragePathPolicy;
use RuntimeException;
use Throwable;

final class OrganizationUnitDocumentService
{
    public function __construct(
        private readonly OrganizationUnitDocumentModel $documents,
        private readonly OrganizationUnitModel $units,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly OrganizationUnitContext $context,
        private readonly OrganizationUnitAssetStorageService $assets,
        private readonly TenantStoragePathPolicy $paths,
        private readonly PrivateObjectStorageInterface $files,
        private readonly OrganizationUnitAuditService $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $filters */
    public function page(int $organizationUnitId, array $filters, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            $this->requireUnit($tenantId, $organizationUnitId, false);
            $query = $this->documents->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId);
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('original_filename', 'like', '%'.$search.'%')
                        ->orWhere('document_type', 'like', '%'.$search.'%');
                });
            }
            $documentType = trim((string) ($filters['document_type'] ?? ''));
            if ($documentType !== '') {
                $query->where('document_type', $documentType);
            }

            $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
            $items = array_map(
                fn (OrganizationUnitDocumentModel $document): DataRecord => $this->record($document),
                $paginator->items(),
            );

            return Result::success(new PagedResult(
                $items,
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage(),
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-document.list');
        }
    }

    public function get(int $organizationUnitId, int|string $id): Result
    {
        try {
            $document = $this->findScoped($organizationUnitId, $id);

            return $document instanceof OrganizationUnitDocumentModel
                ? Result::success($this->record($document))
                : Result::failure(new Error(
                    OrganizationUnitErrorCode::NOT_FOUND,
                    'Organization-unit document not found.',
                ));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-document.get');
        }
    }

    /** @param array<string, mixed> $payload */
    public function create(int $organizationUnitId, array $payload): Result
    {
        $stored = null;
        $committed = false;

        try {
            $tenantId = $this->context->requireTenantId();
            $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
            $stored = $this->assets->storeDocument(
                $tenantId,
                $organizationUnitId,
                (string) ($payload['file_tmp_path'] ?? ''),
                (string) ($payload['file_original_name'] ?? 'document.bin'),
            );

            $document = DB::transaction(function () use (
                $tenantId,
                $organizationUnitId,
                $payload,
                $name,
                $stored,
            ): OrganizationUnitDocumentModel {
                $this->requireUnit($tenantId, $organizationUnitId, true, true);
                if ($this->nameExists($tenantId, $organizationUnitId, $name)) {
                    throw OrganizationUnitException::conflict('Document name already exists for this organization unit.');
                }

                $document = new OrganizationUnitDocumentModel();
                $document->forceFill([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $name,
                    'active_name_hash' => OrganizationUnitNameKey::from($name),
                    'document_type' => $this->domain->normalizeOptionalText(
                        $this->nullableString($payload['document_type'] ?? null),
                        100,
                    ),
                    ...$stored,
                    'row_version' => 1,
                ])->save();
                $document->refresh();
                $this->audit->document('created', $document, null, $document->attributesToArray());

                return $document;
            }, 3);
            $committed = true;

            return Result::success($this->record($document));
        } catch (Throwable $exception) {
            if (! $committed && is_array($stored)) {
                $this->assets->discardUnreferencedAsset(
                    $this->context->requireTenantId(),
                    $stored['object_key'] ?? null,
                    'failed organization-unit document create',
                );
            }

            return $this->failure($exception, 'organization-unit-document.create');
        }
    }

    /** @param array<string, mixed> $payload */
    public function update(int $organizationUnitId, int|string $id, array $payload): Result
    {
        $replacement = null;
        $committed = false;
        $oldObjectKey = null;

        try {
            $tenantId = $this->context->requireTenantId();
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                throw OrganizationUnitException::invalid('The current document version is required.');
            }
            if (isset($payload['file_tmp_path'])) {
                $replacement = $this->assets->storeDocument(
                    $tenantId,
                    $organizationUnitId,
                    (string) $payload['file_tmp_path'],
                    (string) ($payload['file_original_name'] ?? 'document.bin'),
                );
            }

            $document = DB::transaction(function () use (
                $tenantId,
                $organizationUnitId,
                $id,
                $payload,
                $expectedVersion,
                $replacement,
                &$oldObjectKey,
            ): OrganizationUnitDocumentModel {
                $this->requireUnit($tenantId, $organizationUnitId, true, true);
                $existing = $this->findScoped($organizationUnitId, $id, false, true);
                if (! $existing instanceof OrganizationUnitDocumentModel) {
                    throw OrganizationUnitException::notFound('Organization-unit document not found.');
                }
                $this->assertVersion($existing, $expectedVersion);
                $before = $existing->attributesToArray();

                $name = array_key_exists('name', $payload)
                    ? $this->domain->normalizeName((string) $payload['name'])
                    : (string) $existing->getAttribute('name');
                if ($this->nameExists($tenantId, $organizationUnitId, $name, (int) $existing->getKey())) {
                    throw OrganizationUnitException::conflict('Document name already exists for this organization unit.');
                }

                if ($replacement !== null) {
                    $oldObjectKey = $this->nullableString($existing->getAttribute('object_key'));
                }
                $existing->forceFill([
                    'name' => $name,
                    'active_name_hash' => OrganizationUnitNameKey::from($name),
                    'document_type' => array_key_exists('document_type', $payload)
                        ? $this->domain->normalizeOptionalText(
                            $this->nullableString($payload['document_type']),
                            100,
                        )
                        : $existing->getAttribute('document_type'),
                    ...($replacement ?? []),
                    'row_version' => $expectedVersion + 1,
                ])->save();

                if ($replacement !== null) {
                    $this->assets->scheduleCleanup(
                        $tenantId,
                        $oldObjectKey,
                        'organization-unit document replacement',
                    );
                }
                $existing->refresh();
                $this->audit->document('updated', $existing, $before, $existing->attributesToArray());

                return $existing;
            }, 3);
            $committed = true;
            $this->assets->processCleanup($tenantId, $oldObjectKey);

            return Result::success($this->record($document));
        } catch (Throwable $exception) {
            if (! $committed && is_array($replacement)) {
                $this->assets->discardUnreferencedAsset(
                    $this->context->requireTenantId(),
                    $replacement['object_key'] ?? null,
                    'failed organization-unit document update',
                );
            }

            return $this->failure($exception, 'organization-unit-document.update');
        }
    }

    public function delete(int $organizationUnitId, int|string $id, int $expectedVersion): Result
    {
        try {
            if ($expectedVersion < 1) {
                throw OrganizationUnitException::invalid('The current document version is required.');
            }
            $tenantId = $this->context->requireTenantId();
            $objectKey = null;

            DB::transaction(function () use (
                $tenantId,
                $organizationUnitId,
                $id,
                $expectedVersion,
                &$objectKey,
            ): void {
                $existing = $this->findScoped($organizationUnitId, $id, false, true);
                if (! $existing instanceof OrganizationUnitDocumentModel) {
                    throw OrganizationUnitException::notFound('Organization-unit document not found.');
                }
                $this->assertVersion($existing, $expectedVersion);
                $before = $existing->attributesToArray();
                $objectKey = $this->nullableString($existing->getAttribute('object_key'));
                $existing->forceFill([
                    'active_name_hash' => null,
                    'row_version' => $expectedVersion + 1,
                    'deleted_at' => now(),
                ])->save();
                $this->assets->scheduleCleanup(
                    $tenantId,
                    $objectKey,
                    'organization-unit document deletion',
                );
                $this->audit->document('deleted', $existing, $before, null);
            }, 3);

            $this->assets->processCleanup($tenantId, $objectKey);

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-document.delete');
        }
    }

    public function download(int $organizationUnitId, int|string $id): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            $document = $this->findScoped($organizationUnitId, $id);
            if (! $document instanceof OrganizationUnitDocumentModel) {
                return Result::failure(new Error(
                    OrganizationUnitErrorCode::NOT_FOUND,
                    'Organization-unit document not found.',
                ));
            }
            $path = $this->paths->resolveObjectKey(
                $tenantId,
                (string) $document->getAttribute('object_key'),
            );
            $stream = $this->files->readStream($path, $this->disk());
            if (! is_resource($stream)) {
                throw new RuntimeException('Stored organization-unit document could not be opened.');
            }

            return Result::success([
                'record' => $this->record($document),
                'stream' => $stream,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-document.download');
        }
    }

    private function requireUnit(
        int $tenantId,
        int $organizationUnitId,
        bool $mustBeActive,
        bool $lockForUpdate = false,
    ): OrganizationUnitModel {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId);
        if ($mustBeActive) {
            $query->where('is_active', true)->whereNull('retired_at');
        }
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $unit = $query->first();
        if (! $unit instanceof OrganizationUnitModel) {
            throw $mustBeActive
                ? OrganizationUnitException::invalid('Select an active organization unit from the current tenant.')
                : OrganizationUnitException::notFound('Organization unit was not found in the current tenant.');
        }

        return $unit;
    }

    private function findScoped(
        int $organizationUnitId,
        int|string $id,
        bool $required = false,
        bool $lockForUpdate = false,
    ): ?OrganizationUnitDocumentModel {
        $query = $this->documents->newQuery()
            ->where('tenant_id', $this->context->requireTenantId())
            ->where('organization_unit_id', $organizationUnitId)
            ->whereKey($id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $document = $query->first();
        if ($required && ! $document instanceof OrganizationUnitDocumentModel) {
            throw OrganizationUnitException::notFound('Organization-unit document not found.');
        }

        return $document;
    }

    private function nameExists(
        int $tenantId,
        int $organizationUnitId,
        string $name,
        ?int $excludeId = null,
    ): bool {
        $query = $this->documents->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('active_name_hash', OrganizationUnitNameKey::from($name));
        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->exists();
    }

    private function assertVersion(OrganizationUnitDocumentModel $document, int $expectedVersion): void
    {
        if ((int) $document->getAttribute('row_version') !== $expectedVersion) {
            throw OrganizationUnitException::versionConflict('Document changed since it was loaded. Refresh and try again.');
        }
    }

    private function record(OrganizationUnitDocumentModel $document): DataRecord
    {
        $payload = $document->attributesToArray();
        unset($payload['object_key'], $payload['active_name_hash']);
        $payload['download_available'] = true;

        return new DataRecord($payload);
    }

    private function failure(Throwable $exception, string $operation): Result
    {
        if ($exception instanceof OrganizationUnitException) {
            return Result::failure(new Error(
                $exception->errorCode(),
                $exception->getMessage(),
                [...$exception->context(), 'operation' => $operation],
            ));
        }

        return Result::failure($this->errors->normalize(
            $exception,
            OrganizationUnitErrorCode::INVALID_VALUE,
            ['operation' => $operation],
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : trim((string) $value);
    }

    private function disk(): string
    {
        $disk = trim((string) config(
            'organization-unit.storage.disk',
            config('tenant.documents.disk', 'tenant_private'),
        ));
        if ($disk === '') {
            throw new RuntimeException('Organization-unit private storage is not configured.');
        }

        return $disk;
    }
}
