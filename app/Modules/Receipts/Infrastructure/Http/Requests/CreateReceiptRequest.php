<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Receipts\Domain\ValueObjects\ReceiptType;

class CreateReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', array_map(static fn (ReceiptType $type): string => $type->value, ReceiptType::cases()));

        return [
            'org_unit_id' => ['nullable', 'uuid'],
            'receipt_number' => ['required', 'string', 'max:64'],
            'payment_id' => ['required', 'uuid'],
            'invoice_id' => ['nullable', 'uuid'],
            'receipt_type' => ['required', 'string', "in:{$types}"],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
