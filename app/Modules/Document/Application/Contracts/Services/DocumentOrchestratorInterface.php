<?php

declare(strict_types=1);

namespace Modules\Document\Application\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Domain\Aggregates\DocumentAggregate;

interface DocumentOrchestratorInterface
{
    public function create(CreateDocumentDTO $dto): DocumentAggregate;

    public function show(int $tenantId, int $documentId): DocumentAggregate;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function changeStatus(
        int $tenantId,
        int $documentId,
        string $toStatus,
        ?string $actionName = null
    ): DocumentAggregate;

    /**
     * @return array<string, mixed>
     */
    public function uploadAttachment(int $tenantId, int $documentId, UploadedFile $file): array;

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
    public function updatePermissions(int $tenantId, int $documentId, array $permissions): array;

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
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function previewTemplate(int $tenantId, array $input): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listWorkflows(int $tenantId): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function previewDocumentDefinition(int $tenantId, array $input): array;

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
