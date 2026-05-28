<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Supplier\Domain\Constants\SupplierStatus;

final class SupplierStatusTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', SupplierStatus::values())],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
