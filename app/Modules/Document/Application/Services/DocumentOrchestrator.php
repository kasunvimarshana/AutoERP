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
    public function listDocumentTypes(): array
    {
        return $this->documentRepository->listDocumentTypes();
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
