<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalYearRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail): void {
                $startDate = $this->input('start_date');
                if ($startDate !== null && is_string($startDate) && strtotime($value) < strtotime($startDate)) {
                    $fail("The {$attribute} must be a date after or equal to start date.");
                }
            }],
            'status' => ['sometimes', 'in:open,closed'],
        ];
    }
}
