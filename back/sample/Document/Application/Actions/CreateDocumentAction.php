<?php

namespace Modules\Document\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Entities\Document;
use Modules\Document\Domain\Entities\DocumentItem;
use Modules\Document\Domain\Exceptions\DocumentValidationException;
use Modules\Document\Domain\Repositories\DocumentDefinitionRepositoryInterface;
use Modules\Document\Domain\Services\DocumentDomainService;

class CreateDocumentAction
{
    public function __construct(
        private readonly DocumentDefinitionRepositoryInterface $definitionRepository,
        private readonly DocumentDomainService $domainService,
        private readonly CalculateItemTotalAction $calculateItemTotalAction,
    ) {}

    public function execute(CreateDocumentDTO $dto, string $documentNumber): DocumentAggregate
    {
        $definition = $this->definitionRepository->findActive($dto->tenantId, $dto->documentTypeId);

        if ($definition !== null) {
            $this->domainService->validateHeaderDefinition($dto->data, $definition->headerSchema);
        }

        $document = new Document(
            id: null,
            tenantId: $dto->tenantId,
            organizationUnitId: $dto->organizationUnitId,
            documentTypeId: $dto->documentTypeId,
            documentNumber: $documentNumber,
            documentDate: $dto->documentDate,
            dueDate: $dto->dueDate,
            status: DB::table('document_types')->where('id', $dto->documentTypeId)->value('default_status') ?? 'draft',
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
        );

        $items = [];

        foreach ($dto->items as $index => $itemPayload) {
            $itemType = (string) ($itemPayload['item_type'] ?? 'inventory');
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

            $this->domainService->validateItemDefinition($itemData, $itemType, $dto->tenantId);

            $lineTotal = $this->calculateItemTotalAction->execute(
                array_merge($itemPayload, $itemData),
                $itemType,
                $dto->tenantId,
            );

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
}
