<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', array_map(static fn (InvoiceType $type): string => $type->value, InvoiceType::cases()));
        $entityTypes = implode(',', array_map(static fn (InvoiceEntityType $type): string => $type->value, InvoiceEntityType::cases()));

        return [
            'org_unit_id' => ['nullable', 'uuid'],
            'invoice_number' => ['required', 'string', 'max:64'],
            'invoice_type' => ['required', 'string', "in:{$types}"],
            'entity_type' => ['required', 'string', "in:{$entityTypes}"],
            'entity_id' => ['nullable', 'uuid'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'subtotal_amount' => ['required', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'total_amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
