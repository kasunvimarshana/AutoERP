<?php

namespace Modules\Document\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Document\Application\Actions\ChangeDocumentStatusAction;
use Modules\Document\Application\Actions\CreateDocumentAction;
use Modules\Document\Application\Actions\UploadDocumentAttachmentAction;
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
        private readonly SequenceService $sequenceService,
    ) {}

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

    public function show(int $documentId): DocumentAggregate
    {
        return $this->getDocumentQuery->execute($documentId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listDocumentsQuery->execute($filters, $perPage);
    }

    public function changeStatus(int $documentId, string $toStatus, ?string $actionName = null): DocumentAggregate
    {
        return DB::transaction(function () use ($documentId, $toStatus, $actionName): DocumentAggregate {
            $aggregate = $this->getDocumentQuery->execute($documentId);
            $updatedAggregate = $this->changeDocumentStatusAction->execute($aggregate, $toStatus, $actionName);

            return $this->documentRepository->update($updatedAggregate);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadAttachment(int $documentId, UploadedFile $file): array
    {
        return DB::transaction(function () use ($documentId, $file): array {
            $this->getDocumentQuery->execute($documentId);

            $payload = $this->uploadDocumentAttachmentAction->execute($file);

            return $this->documentRepository->storeAttachment($documentId, $payload);
        });
    }
}
