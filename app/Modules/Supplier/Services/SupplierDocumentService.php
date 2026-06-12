<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Supplier\DTOs\SupplierDocumentData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierDocument;

final class SupplierDocumentService
{
    public function create(Supplier $supplier, SupplierDocumentData $data): SupplierDocument
    {
        if ($data->issuedDate !== null && $data->expiryDate !== null && $data->issuedDate > $data->expiryDate) {
            throw new InvalidArgumentException('Supplier document issued date cannot be after expiry date.');
        }

        return $supplier->documents()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            'file_path' => $data->filePath,
            'status' => $data->status,
            'notes' => $data->notes,
        ]);
    }

    public function update(Supplier $supplier, SupplierDocument $document, SupplierDocumentData $data): SupplierDocument
    {
        $this->assertOwned($supplier, $document);
        if ($data->issuedDate !== null && $data->expiryDate !== null && $data->issuedDate > $data->expiryDate) {
            throw new InvalidArgumentException('Supplier document issued date cannot be after expiry date.');
        }

        $document->fill([
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            'file_path' => $data->filePath,
            'status' => $data->status,
            'notes' => $data->notes,
        ])->save();

        return $document->refresh();
    }

    public function delete(Supplier $supplier, SupplierDocument $document): void
    {
        $this->assertOwned($supplier, $document);
        $document->delete();
    }

    /**
     * @param  list<SupplierDocumentData>  $documents
     */
    public function replace(Supplier $supplier, array $documents): void
    {
        $supplier->documents()->delete();
        foreach ($documents as $document) {
            $this->create($supplier, $document);
        }
    }

    private function assertOwned(Supplier $supplier, SupplierDocument $document): void
    {
        if ((int) $document->supplier_id !== (int) $supplier->getKey()) {
            throw new InvalidArgumentException('Supplier document does not belong to the supplier.');
        }
    }
}
