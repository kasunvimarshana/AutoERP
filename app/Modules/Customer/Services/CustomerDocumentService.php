<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerDocumentData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDocument;

final class CustomerDocumentService
{
    public function create(Customer $customer, CustomerDocumentData $data): CustomerDocument
    {
        if ($data->issuedDate !== null && $data->expiryDate !== null && $data->issuedDate > $data->expiryDate) {
            throw new InvalidArgumentException('Customer document issued date cannot be after expiry date.');
        }

        return $customer->documents()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            'status' => $data->status,
            'notes' => $data->notes,
        ]);
    }

    public function update(Customer $customer, CustomerDocument $document, CustomerDocumentData $data): CustomerDocument
    {
        $this->assertOwned($customer, $document);
        if ($data->issuedDate !== null && $data->expiryDate !== null && $data->issuedDate > $data->expiryDate) {
            throw new InvalidArgumentException('Customer document issued date cannot be after expiry date.');
        }

        $document->fill([
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            'status' => $data->status,
            'notes' => $data->notes,
        ])->save();

        return $document->refresh();
    }

    public function delete(Customer $customer, CustomerDocument $document): void
    {
        $this->assertOwned($customer, $document);
        $document->delete();
    }

    /**
     * @param  list<CustomerDocumentData>  $documents
     */
    public function replace(Customer $customer, array $documents): void
    {
        $customer->documents()->delete();
        foreach ($documents as $document) {
            $this->create($customer, $document);
        }
    }

    private function assertOwned(Customer $customer, CustomerDocument $document): void
    {
        if ((int) $document->customer_id !== (int) $customer->getKey()) {
            throw new InvalidArgumentException('Customer document does not belong to the customer.');
        }
    }
}
