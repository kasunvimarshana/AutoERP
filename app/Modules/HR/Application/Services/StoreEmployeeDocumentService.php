<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\StoreEmployeeDocumentServiceInterface;
use Modules\HR\Application\DTOs\EmployeeDocumentData;
use Modules\HR\Domain\Entities\EmployeeDocument;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeDocumentRepositoryInterface;

class StoreEmployeeDocumentService extends BaseService implements StoreEmployeeDocumentServiceInterface
{
    public function __construct(
        private readonly EmployeeDocumentRepositoryInterface $documentRepository,
    ) {
        parent::__construct($this->documentRepository);
    }

    protected function handle(array $data): EmployeeDocument
    {
        $dto = EmployeeDocumentData::fromArray($data);

        $now = new \DateTimeImmutable;
        $document = new EmployeeDocument(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            documentType: $dto->documentType,
            title: $dto->title,
            description: $dto->description,
            filePath: $dto->filePath,
            mimeType: $dto->mimeType,
            fileSize: $dto->fileSize,
            issuedDate: $dto->issuedDate !== null ? new \DateTimeImmutable($dto->issuedDate) : null,
            expiryDate: $dto->expiryDate !== null ? new \DateTimeImmutable($dto->expiryDate) : null,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->documentRepository->save($document);
    }
}
