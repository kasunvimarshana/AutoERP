<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;

class ChangeReturnStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $values = array_column(ReturnStatus::cases(), 'value');

        return [
            'status' => ['required', 'string', Rule::in($values)],
        ];
    }
}
