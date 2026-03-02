<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Procurement\Domain\Enums\SupplierStatus;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_column(SupplierStatus::cases(), 'value'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', "in:{$statuses}"],
            'notes' => ['nullable', 'string'],
        ];
    }
}
