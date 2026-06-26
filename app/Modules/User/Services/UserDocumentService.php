<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserDocumentType;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserDocumentModel;
use Modules\User\Models\UserModel;
use Modules\User\Services\Audit\UserAuditService;
use Modules\User\Services\Storage\UserAssetStorageService;
use RuntimeException;
use Throwable;

final class UserDocumentService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly UserAssetStorageService $assets,
        private readonly UserAuditService $audit,
    ) {}

    public function list(int|string $userId, array $filters): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_VIEW);
            $this->requireUser($tenantId, $userId);
            $query = UserDocumentModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId);
            $type = trim((string) ($filters['document_type'] ?? ''));
            if ($type !== '') {
                $query->where('document_type', $type);
            }
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where('name', 'like', $search.'%');
            }
            $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);
            $page = max((int) ($filters['page'] ?? 1), 1);
            $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
            $items = array_map(fn (mixed $document): DataRecord => $this->record($document), array_values($paginator->items()));
            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $userId, int|string $documentId): Result
    {
        try {
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_VIEW);
            $document = $this->find($this->tenantId(), $userId, $documentId, false);
            return $document instanceof UserDocumentModel ? Result::success($this->record($document)) : $this->notFound('User document not found.');
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(int|string $userId, array $payload): Result
    {
        $storedKey = null;
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_MANAGE);
            $user = $this->requireUser($tenantId, $userId);
            $file = $payload['file'] ?? null;
            if (! $file instanceof UploadedFile || ! is_string($file->getRealPath())) {
                throw new RuntimeException('A valid uploaded document is required.');
            }
            $stored = $this->assets->storeDocument($tenantId, (int) $user->getKey(), $file->getRealPath(), $file->getClientOriginalName());
            $storedKey = $stored['object_key'];
            $document = DB::transaction(function () use ($tenantId, $user, $payload, $stored): UserDocumentModel {
                $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $name = trim((string) ($payload['name'] ?? ''));
                $type = strtolower(trim((string) ($payload['document_type'] ?? '')));
                if (! in_array($type, UserDocumentType::values(), true)) {
                    throw new RuntimeException('Document type is invalid.');
                }
                $this->assertUniqueName($tenantId, (int) $user->getKey(), $name, null);
                $document = UserDocumentModel::query()->create(array_merge($stored, [
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                    'row_version' => 1,
                    'name' => $name,
                    'document_type' => $type,
                    'uploaded_by_user_id' => $this->actor->currentUserId(),
                ]));
                $this->audit->record('document.created', 'user_document', $document, null, $document->attributesToArray());
                return $document;
            }, 3);
            return Result::success($this->record($document));
        } catch (Throwable $exception) {
            if ($storedKey !== null) {
                $this->assets->discardUnreferenced($this->tenantId(), $storedKey, 'failed user document creation');
            }
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $userId, int|string $documentId, int $expectedVersion, array $payload): Result
    {
        $newKey = null;
        $oldKey = null;
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_MANAGE);
            $file = $payload['file'] ?? null;
            $stored = null;
            if ($file instanceof UploadedFile && is_string($file->getRealPath())) {
                $stored = $this->assets->storeDocument($tenantId, (int) $userId, $file->getRealPath(), $file->getClientOriginalName());
                $newKey = $stored['object_key'];
            }
            $document = DB::transaction(function () use (
                $tenantId, $userId, $documentId, $expectedVersion, $payload, $stored, &$oldKey,
            ): UserDocumentModel {
                $document = $this->find($tenantId, $userId, $documentId, true);
                if (! $document instanceof UserDocumentModel) {
                    throw new RuntimeException('User document not found.');
                }
                if ($expectedVersion < 1 || (int) $document->getAttribute('row_version') !== $expectedVersion) {
                    throw new RuntimeException('The document changed after it was loaded. Refresh and try again.');
                }
                $before = $document->attributesToArray();
                $name = array_key_exists('name', $payload) ? trim((string) $payload['name']) : (string) $document->getAttribute('name');
                $this->assertUniqueName($tenantId, (int) $userId, $name, (int) $document->getKey());
                $type = array_key_exists('document_type', $payload)
                    ? strtolower(trim((string) $payload['document_type']))
                    : (string) $document->getAttribute('document_type');
                if (! in_array($type, UserDocumentType::values(), true)) {
                    throw new RuntimeException('Document type is invalid.');
                }
                $oldKey = $stored === null ? null : (string) $document->getAttribute('object_key');
                $document->forceFill(array_merge($stored ?? [], [
                    'name' => $name,
                    'document_type' => $type,
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $this->actor->currentUserId(),
                ]))->save();
                if ($oldKey !== null) {
                    $this->assets->scheduleCleanup($tenantId, $oldKey, 'user document replacement');
                }
                $this->audit->record('document.updated', 'user_document', $document, $before, $document->attributesToArray());
                return $document;
            }, 3);
            $this->assets->processCleanup($tenantId, $oldKey);
            return Result::success($this->record($document));
        } catch (Throwable $exception) {
            if ($newKey !== null) {
                $this->assets->discardUnreferenced($this->tenantId(), $newKey, 'failed user document update');
            }
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $userId, int|string $documentId, int $expectedVersion): Result
    {
        $objectKey = null;
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_MANAGE);
            DB::transaction(function () use ($tenantId, $userId, $documentId, $expectedVersion, &$objectKey): void {
                $document = $this->find($tenantId, $userId, $documentId, true);
                if (! $document instanceof UserDocumentModel) {
                    throw new RuntimeException('User document not found.');
                }
                if ((int) $document->getAttribute('row_version') !== $expectedVersion) {
                    throw new RuntimeException('The document changed after it was loaded. Refresh and try again.');
                }
                $before = $document->attributesToArray();
                $objectKey = (string) $document->getAttribute('object_key');
                $document->forceFill([
                    'deleted_at' => now(),
                    'active_name_key' => null,
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $this->actor->currentUserId(),
                ])->save();
                $this->assets->scheduleCleanup($tenantId, $objectKey, 'user document deletion');
                $this->audit->record('document.deleted', 'user_document', $document, $before, null);
            }, 3);
            $this->assets->processCleanup($tenantId, $objectKey);
            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function download(int|string $userId, int|string $documentId): Result
    {
        try {
            $tenantId = $this->tenantId();
            $this->authorizeSubject((int) $userId, UserPermission::USER_DOCUMENTS_VIEW);
            $document = $this->find($tenantId, $userId, $documentId, false);
            if (! $document instanceof UserDocumentModel) {
                return $this->notFound('User document not found.');
            }
            return Result::success(['record' => $this->record($document), 'stream' => $this->assets->read($tenantId, (string) $document->getAttribute('object_key'))]);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function authorizeSubject(int $subjectUserId, string $managementPermission): void
    {
        if ($subjectUserId === $this->actor->currentUserId()) {
            return;
        }
        if (! $this->authorization->canCurrent($managementPermission)) {
            throw new AuthorizationException('User document access is not authorized.');
        }
    }

    private function requireUser(int $tenantId, int|string $userId): UserModel
    {
        $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->first();
        if (! $user instanceof UserModel) {
            throw new RuntimeException('User not found.');
        }
        return $user;
    }

    private function find(int $tenantId, int|string $userId, int|string $documentId, bool $lock): ?UserDocumentModel
    {
        $query = UserDocumentModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId)->whereKey($documentId);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    private function assertUniqueName(int $tenantId, int $userId, string $name, ?int $excludingId): void
    {
        if ($name === '') {
            throw new RuntimeException('Document name is required.');
        }
        $query = UserDocumentModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId)
            ->where('active_name_key', mb_strtolower($name));
        if ($excludingId !== null) {
            $query->where($query->getModel()->getKeyName(), '!=', $excludingId);
        }
        if ($query->exists()) {
            throw new RuntimeException('An active document with this name already exists for the user.');
        }
    }

    private function record(UserDocumentModel $document): DataRecord
    {
        return new DataRecord([
            'id' => (int) $document->getKey(),
            'row_version' => (int) $document->getAttribute('row_version'),
            'user_id' => (int) $document->getAttribute('user_id'),
            'name' => (string) $document->getAttribute('name'),
            'document_type' => (string) $document->getAttribute('document_type'),
            'original_filename' => (string) $document->getAttribute('original_filename'),
            'mime_type' => (string) $document->getAttribute('mime_type'),
            'size_bytes' => (int) $document->getAttribute('size_bytes'),
            'checksum_sha256' => (string) $document->getAttribute('checksum_sha256'),
            'scanned_at' => $document->getAttribute('scanned_at')?->toAtomString(),
            'created_at' => $document->getAttribute('created_at')?->toAtomString(),
            'updated_at' => $document->getAttribute('updated_at')?->toAtomString(),
        ]);
    }

    private function tenantId(): int
    {
        $id = $this->tenant->currentTenantId();
        if ($id === null) {
            throw new RuntimeException('A tenant context is required.');
        }
        return $id;
    }

}
