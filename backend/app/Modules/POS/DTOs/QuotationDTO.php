<?php

namespace App\Modules\POS\DTOs;

class QuotationDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $userId,
        public readonly string $quotationDate,
        public readonly string $validUntil,
        public readonly array $items,
        public readonly ?string $notes = null,
        public readonly ?string $termsAndConditions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            userId: $data['user_id'],
            quotationDate: $data['quotation_date'],
            validUntil: $data['valid_until'],
            items: $data['items'] ?? [],
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'user_id' => $this->userId,
            'quotation_date' => $this->quotationDate,
            'valid_until' => $this->validUntil,
            'notes' => $this->notes,
            'terms_and_conditions' => $this->termsAndConditions,
        ];
    }
}
