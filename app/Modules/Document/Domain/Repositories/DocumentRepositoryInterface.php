<?php

namespace Modules\Document\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Document\Domain\Aggregates\DocumentAggregate;

interface DocumentRepositoryInterface
{
    public function save(DocumentAggregate $aggregate): DocumentAggregate;

    public function findById(int $tenantId, int $id): ?DocumentAggregate;

    public function update(DocumentAggregate $aggregate): DocumentAggregate;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function storeAttachment(int $documentId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addComment(int $tenantId, int $documentId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addActivity(int $tenantId, int $documentId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addEvent(int $tenantId, int $documentId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addPermission(int $tenantId, int $documentId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addRelation(int $tenantId, int $documentId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listComments(int $tenantId, int $documentId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActivities(int $tenantId, int $documentId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(int $tenantId, int $documentId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissions(int $tenantId, int $documentId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRelations(int $tenantId, int $documentId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAttachments(int $tenantId, int $documentId): array;

    public function removeAttachment(int $tenantId, int $documentId, int $attachmentId): bool;

    public function removeRelation(int $tenantId, int $documentId, int $relationId): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVersions(int $tenantId, int $documentId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getVersion(int $tenantId, int $documentId, int $versionId): ?array;

    /**
     * @param  array<int, array<string, mixed>>  $permissions
     * @return array<int, array<string, mixed>>
     */
    public function replacePermissions(int $tenantId, int $documentId, array $permissions): array;

    /**
     * @param  array<int, array<string, mixed>>  $metadata
     * @return array<int, array<string, mixed>>
     */
    public function updateDocumentMetadata(int $tenantId, int $documentId, array $metadata): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentLines(int $tenantId, int $documentId): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDocumentType(int $tenantId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentTypes(int $tenantId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getDocumentType(int $tenantId, int $typeId): ?array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDocumentType(int $tenantId, int $typeId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createItemType(int $tenantId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItemTypes(): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDocumentDefinition(int $tenantId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentDefinitions(int $tenantId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getDocumentDefinition(int $tenantId, int $definitionId): ?array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDocumentDefinition(int $tenantId, int $definitionId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTemplates(int $tenantId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getTemplate(int $tenantId, int $templateId): ?array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createTemplate(int $tenantId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateTemplate(int $tenantId, int $templateId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRenderLog(int $tenantId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createItemDefinition(int $tenantId, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItemDefinitions(int $tenantId): array;
}
