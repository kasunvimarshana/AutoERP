<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_end_at' => ['required', 'date'],
            'end_odometer'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
