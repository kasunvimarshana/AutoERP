<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;

class ChangePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_map(static fn (PaymentStatus $status): string => $status->value, PaymentStatus::cases()));

        return [
            'status' => ['required', 'string', "in:{$statuses}"],
        ];
    }
}
