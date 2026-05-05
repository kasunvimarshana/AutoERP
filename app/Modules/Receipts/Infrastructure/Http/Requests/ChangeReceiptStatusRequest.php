<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Receipts\Domain\ValueObjects\ReceiptStatus;

class ChangeReceiptStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_map(static fn (ReceiptStatus $status): string => $status->value, ReceiptStatus::cases()));

        return [
            'status' => ['required', 'string', "in:{$statuses}"],
        ];
    }
}
