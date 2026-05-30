<?php

namespace Modules\Document\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Document\Application\Actions\ChangeDocumentStatusAction;
use Modules\Document\Application\Actions\CreateDocumentAction;
use Modules\Document\Application\Actions\UploadDocumentAttachmentAction;
use Modules\Document\Application\Contracts\SequenceServiceInterface;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Application\Queries\GetDocumentQuery;
use Modules\Document\Application\Queries\ListDocumentsQuery;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Exceptions\DocumentValidationException;
use Modules\Document\Domain\Repositories\DocumentRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentTypeRepositoryInterface;

class DocumentOrchestrator
{
    public function __construct(
        private readonly CreateDocumentAction $createDocumentAction,
        private readonly ChangeDocumentStatusAction $changeDocumentStatusAction,
        private readonly UploadDocumentAttachmentAction $uploadDocumentAttachmentAction,
        private readonly ListDocumentsQuery $listDocumentsQuery,
        private readonly GetDocumentQuery $getDocumentQuery,
        private readonly DocumentRepositoryInterface $documentRepository,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly SequenceServiceInterface $sequenceService,
    ) {
    }

    public function create(CreateDocumentDTO $dto): DocumentAggregate
    {
        return DB::transaction(function () use ($dto): DocumentAggregate {
            $documentTypeCode = $this->documentTypeRepository->findCodeById($dto->documentTypeId);
            if ($documentTypeCode === null) {
                throw new DocumentValidationException("Document type [{$dto->documentTypeId}] does not exist.");
            }

            $documentNumber = $this->sequenceService->nextNumber(
                $dto->tenantId,
                $dto->organizationUnitId,
                $documentTypeCode,
                $dto->documentDate,
            );

            $aggregate = $this->createDocumentAction->execute($dto, $documentNumber);

            return $this->documentRepository->save($aggregate);
        });
    }

    public function show(int $tenantId, int $documentId): DocumentAggregate
    {
        return $this->getDocumentQuery->execute($tenantId, $documentId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listDocumentsQuery->execute($filters, $perPage);
    }

    public function changeStatus(
        int $tenantId,
        int $documentId,
        string $toStatus,
        ?string $actionName = null
    ): DocumentAggregate {
        return DB::transaction(function () use ($tenantId, $documentId, $toStatus, $actionName): DocumentAggregate {
            $aggregate = $this->getDocumentQuery->execute($tenantId, $documentId);
            $updatedAggregate = $this->changeDocumentStatusAction->execute($aggregate, $toStatus, $actionName);

            return $this->documentRepository->update($updatedAggregate);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadAttachment(int $tenantId, int $documentId, UploadedFile $file): array
    {
        return DB::transaction(function () use ($tenantId, $documentId, $file): array {
            $this->getDocumentQuery->execute($tenantId, $documentId);

            $payload = $this->uploadDocumentAttachmentAction->execute($file);
            $payload['tenant_id'] = $tenantId;

            return $this->documentRepository->storeAttachment($documentId, $payload);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addComment(int $tenantId, int $documentId, array $payload): array
    {
        return $this->documentRepository->addComment($tenantId, $documentId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addActivity(int $tenantId, int $documentId, array $payload): array
    {
        return $this->documentRepository->addActivity($tenantId, $documentId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addEvent(int $tenantId, int $documentId, array $payload): array
    {
        return $this->documentRepository->addEvent($tenantId, $documentId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addPermission(int $tenantId, int $documentId, array $payload): array
    {
        return $this->documentRepository->addPermission($tenantId, $documentId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addRelation(int $tenantId, int $documentId, array $payload): array
    {
        return $this->documentRepository->addRelation($tenantId, $documentId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listComments(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listComments($tenantId, $documentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActivities(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listActivities($tenantId, $documentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listEvents($tenantId, $documentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissions(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listPermissions($tenantId, $documentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRelations(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listRelations($tenantId, $documentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAttachments(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listAttachments($tenantId, $documentId);
    }

    public function removeAttachment(int $tenantId, int $documentId, int $attachmentId): bool
    {
        return $this->documentRepository->removeAttachment($tenantId, $documentId, $attachmentId);
    }

    public function removeRelation(int $tenantId, int $documentId, int $relationId): bool
    {
        return $this->documentRepository->removeRelation($tenantId, $documentId, $relationId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVersions(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listVersions($tenantId, $documentId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVersion(int $tenantId, int $documentId, int $versionId): ?array
    {
        return $this->documentRepository->getVersion($tenantId, $documentId, $versionId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $permissions
     * @return array<int, array<string, mixed>>
     */
    public function updatePermissions(int $tenantId, int $documentId, array $permissions): array
    {
        return $this->documentRepository->replacePermissions($tenantId, $documentId, $permissions);
    }

    /**
     * @param  array<int, array<string, mixed>>  $metadata
     * @return array<int, array<string, mixed>>
     */
    public function updateDocumentMetadata(int $tenantId, int $documentId, array $metadata): array
    {
        return $this->documentRepository->updateDocumentMetadata($tenantId, $documentId, $metadata);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentLines(int $tenantId, int $documentId): array
    {
        return $this->documentRepository->listDocumentLines($tenantId, $documentId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDocumentType(int $tenantId, array $payload): array
    {
        return $this->documentRepository->createDocumentType($tenantId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentTypes(int $tenantId): array
    {
        return $this->documentRepository->listDocumentTypes($tenantId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDocumentType(int $tenantId, int $typeId): ?array
    {
        return $this->documentRepository->getDocumentType($tenantId, $typeId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDocumentType(int $tenantId, int $typeId, array $payload): array
    {
        return $this->documentRepository->updateDocumentType($tenantId, $typeId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createItemType(int $tenantId, array $payload): array
    {
        return $this->documentRepository->createItemType($tenantId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItemTypes(): array
    {
        return $this->documentRepository->listItemTypes();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDocumentDefinition(int $tenantId, array $payload): array
    {
        return $this->documentRepository->createDocumentDefinition($tenantId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentDefinitions(int $tenantId): array
    {
        return $this->documentRepository->listDocumentDefinitions($tenantId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDocumentDefinition(int $tenantId, int $definitionId): ?array
    {
        return $this->documentRepository->getDocumentDefinition($tenantId, $definitionId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDocumentDefinition(int $tenantId, int $definitionId, array $payload): array
    {
        return $this->documentRepository->updateDocumentDefinition($tenantId, $definitionId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTemplates(int $tenantId): array
    {
        return $this->documentRepository->listTemplates($tenantId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTemplate(int $tenantId, int $templateId): ?array
    {
        return $this->documentRepository->getTemplate($tenantId, $templateId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createTemplate(int $tenantId, array $payload): array
    {
        return $this->documentRepository->createTemplate($tenantId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateTemplate(int $tenantId, int $templateId, array $payload): array
    {
        return $this->documentRepository->updateTemplate($tenantId, $templateId, $payload);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function previewTemplate(int $tenantId, array $input): array
    {
        $template = isset($input['template_id'])
            ? $this->documentRepository->getTemplate($tenantId, (int) $input['template_id'])
            : null;

        $rendered = strtr(
            (string) (($template['body_content'] ?? null) ?: '{{document_title}}' . PHP_EOL . '{{document_body}}'),
            [
                '{{document_title}}' => (string) ($input['title'] ?? 'Document Preview'),
                '{{document_body}}' => (string) ($input['body'] ?? 'Backend-rendered preview body'),
                '{{document_number}}' => (string) ($input['document_number'] ?? 'PREVIEW'),
            ],
        );

        $this->documentRepository->createRenderLog($tenantId, [
            'document_template_id' => $template['id'] ?? null,
            'render_type' => 'template-preview',
            'status' => 'rendered',
            'message' => 'Template preview rendered.',
        ]);

        return [
            'input' => $input,
            'rendered' => [
                'html' => nl2br(e($rendered)),
                'text' => $rendered,
                'template_id' => $template['id'] ?? null,
                'template_name' => $template['template_name'] ?? null,
            ],
            'metadata' => [
                'official' => false,
                'business_logic_free' => true,
            ],
            'warnings' => [],
            'errors' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createItemDefinition(int $tenantId, array $payload): array
    {
        return $this->documentRepository->createItemDefinition($tenantId, $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItemDefinitions(int $tenantId): array
    {
        return $this->documentRepository->listItemDefinitions($tenantId);
    }
}
