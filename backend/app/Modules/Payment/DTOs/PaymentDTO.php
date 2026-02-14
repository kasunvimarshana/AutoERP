<?php

namespace App\Modules\Payment\DTOs;

class PaymentDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $customer_id = null,
        public ?string $invoice_id = null,
        public ?string $payment_number = null,
        public ?string $payment_date = null,
        public ?string $payment_method = null,
        public ?float $amount = 0.00,
        public ?string $currency = 'USD',
        public ?string $status = 'pending',
        public ?string $reference_number = null,
        public ?string $transaction_id = null,
        public ?string $notes = null,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            invoice_id: $data['invoice_id'] ?? null,
            payment_number: $data['payment_number'] ?? null,
            payment_date: $data['payment_date'] ?? null,
            payment_method: $data['payment_method'] ?? null,
            amount: isset($data['amount']) ? (float)$data['amount'] : 0.00,
            currency: $data['currency'] ?? 'USD',
            status: $data['status'] ?? 'pending',
            reference_number: $data['reference_number'] ?? null,
            transaction_id: $data['transaction_id'] ?? null,
            notes: $data['notes'] ?? null,
            settings: $data['settings'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'reference_number' => $this->reference_number,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
