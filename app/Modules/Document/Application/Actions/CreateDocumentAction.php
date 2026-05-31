<?php

namespace Modules\Document\Application\Actions;

use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Entities\Document;
use Modules\Document\Domain\Entities\DocumentItem;
use Modules\Document\Domain\Exceptions\DocumentValidationException;
use Modules\Document\Domain\Repositories\DocumentDefinitionRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentTypeRepositoryInterface;

class CreateDocumentAction
{
    public function __construct(
        private readonly DocumentDefinitionRepositoryInterface $definitionRepository,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly DocumentFieldValidationService $fieldValidationService,
    ) {
    }

    public function execute(CreateDocumentDTO $dto, string $documentNumber): DocumentAggregate
    {
        $definition = $this->definitionRepository->findActive($dto->tenantId, $dto->documentTypeId);

        if ($definition !== null) {
            $this->fieldValidationService->validateHeaderData($dto->data, $definition->headerSchema);
        }

        $defaultStatus = $this->documentTypeRepository->findDefaultStatusById($dto->documentTypeId) ?? 'draft';

        $document = new Document(
            id: null,
            tenantId: $dto->tenantId,
            organizationUnitId: $dto->organizationUnitId,
            documentTypeId: $dto->documentTypeId,
            documentNumber: $documentNumber,
            documentDate: $dto->documentDate,
            dueDate: $dto->dueDate,
            status: $defaultStatus,
            ownerId: $dto->ownerId,
            partyId: $dto->partyId,
            subtotal: '0.0000',
            discountTotal: '0.0000',
            taxTotal: '0.0000',
            grandTotal: '0.0000',
            data: $dto->data,
            notes: $dto->notes,
            createdBy: null,
            updatedBy: null,
            documentDefinitionId: $dto->documentDefinitionId ?? $definition?->id,
            sourceModule: $dto->sourceModule,
            sourceType: $dto->sourceType,
            sourceId: $dto->sourceId,
            sourceReference: $dto->sourceReference,
            title: $dto->title,
        );

        $items = [];

        foreach ($dto->items as $index => $itemPayload) {
            $itemType = (string) ($itemPayload['item_type'] ?? 'generic');
            $itemData = is_array($itemPayload['data'] ?? null) ? $itemPayload['data'] : [];

            if (
                $definition !== null
                && $definition->allowedItemTypes !== []
                && ! in_array($itemType, $definition->allowedItemTypes, true)
            ) {
                throw new DocumentValidationException(
                    "Item type [{$itemType}] is not allowed for this document definition."
                );
            }

            $this->fieldValidationService->validateItemData($itemData, $itemType, $dto->tenantId);

            $lineTotal = $this->normalizeLineTotal($itemPayload['line_total'] ?? 0);

            $items[] = new DocumentItem(
                id: null,
                documentId: 0,
                itemType: $itemType,
                description: $itemPayload['description'] ?? null,
                lineTotal: $lineTotal,
                sequence: $index + 1,
                data: $itemData,
            );
        }

        $aggregate = new DocumentAggregate($document, $items);
        $aggregate->validate();
        $aggregate->calculateTotals();

        return $aggregate;
    }

    private function normalizeLineTotal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        if (! is_numeric($value)) {
            throw new DocumentValidationException('Each item line_total must be numeric.');
        }

        return number_format((float) $value, 4, '.', '');
    }
}
