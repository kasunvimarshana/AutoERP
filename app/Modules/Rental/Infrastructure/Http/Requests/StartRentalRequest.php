<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_start_at' => ['required', 'date'],
            'start_odometer'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
