<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Invoicing\Domain\ValueObjects\InvoiceStatus;

class ChangeInvoiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_map(static fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::cases()));

        return [
            'status' => ['required', 'string', "in:{$statuses}"],
        ];
    }
}
