<?php

namespace App\Modules\Invoice\DTOs;

class InvoiceDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $customer_id = null,
        public ?string $order_id = null,
        public ?string $invoice_number = null,
        public ?string $invoice_date = null,
        public ?string $due_date = null,
        public ?string $status = 'draft',
        public ?float $subtotal = 0.00,
        public ?float $tax_amount = 0.00,
        public ?float $discount_amount = 0.00,
        public ?float $total_amount = 0.00,
        public ?float $paid_amount = 0.00,
        public ?float $balance_due = 0.00,
        public ?string $payment_status = 'unpaid',
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
            order_id: $data['order_id'] ?? null,
            invoice_number: $data['invoice_number'] ?? null,
            invoice_date: $data['invoice_date'] ?? null,
            due_date: $data['due_date'] ?? null,
            status: $data['status'] ?? 'draft',
            subtotal: isset($data['subtotal']) ? (float)$data['subtotal'] : 0.00,
            tax_amount: isset($data['tax_amount']) ? (float)$data['tax_amount'] : 0.00,
            discount_amount: isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.00,
            total_amount: isset($data['total_amount']) ? (float)$data['total_amount'] : 0.00,
            paid_amount: isset($data['paid_amount']) ? (float)$data['paid_amount'] : 0.00,
            balance_due: isset($data['balance_due']) ? (float)$data['balance_due'] : 0.00,
            payment_status: $data['payment_status'] ?? 'unpaid',
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
            'order_id' => $this->order_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
